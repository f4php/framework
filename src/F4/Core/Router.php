<?php

declare(strict_types=1);

namespace F4\Core;

use ReflectionFunction;
use Throwable;

use F4\Core\Exception\HttpException;
use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;
use F4\Core\RouterInterface;
use F4\HookManager;

use function array_reduce;

class Router implements RouterInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;

    protected array $routeGroups = [];
    public function __construct()
    {
        /**
         * This is the default group, all ungrouped routes end up here
         */
        $this->routeGroups[0] = new RouteGroup();
    }
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup
    {
        return $this->routeGroups[] = $routeGroup;
    }
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->routeGroups[0]->addRoute(match ($routeOrPath instanceof Route) {
            true => $routeOrPath,
            default => new Route(pathDefinition: $routeOrPath, handler: $handler)
        });
    }
    public function getMatchingRoute(RequestInterface $request, ResponseInterface $response): ?Route
    {
        return $this->getMatches($request, $response)[1] ?? null;
    }
    public function getMatchingRouteGroup(RequestInterface $request, ResponseInterface $response): ?RouteGroup
    {
        return $this->getMatches($request, $response)[0] ?? null;
    }
    protected function getMatches(RequestInterface $request, ResponseInterface $response): array
    {
        $matchingGroupsData = array_reduce(
            array: $this->routeGroups,
            callback: fn(array $result, RouteGroup $routeGroup): array =>
            match ($route = $routeGroup->getMatchingRoute(request: $request, response: $response)) {
                null => $result,
                default => [
                    ...$result,
                    [
                        'routeGroup' => $routeGroup,
                        'route' => $route,
                    ]
                ]
            },
            initial: [],
        );
        return [
            $matchingGroupsData[0]['routeGroup'] ?? null,
            $matchingGroupsData[0]['route'] ?? null
        ];
    }
    public function invokeMatchingRoutes(RequestInterface &$request, ResponseInterface &$response): mixed
    {
        $result = null;
        $matchingRouteGroup = null;
        $matchingRoute = null;
        /**
         * At most one RouteGroup and at most one Route must match per Request
         */
        [$matchingRouteGroup, $matchingRoute] = $this->getMatches($request, $response);
        try {
            try {
                if (isset($this->requestMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_REQUEST_MIDDLEWARE, context: ['request' => $request, 'middleware' => $this->requestMiddleware]);
                    $request = match (($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $matchingRoute)) instanceof RequestInterface) {
                        true => $requestMiddlewareResult,
                        default => $request
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_REQUEST_MIDDLEWARE, context: ['request' => $request, 'middleware' => $this->requestMiddleware]);
                }
                /**
                 * Need to match again in case the Request was altered by RequestMiddleware
                 */
                [$matchingRouteGroup, $matchingRoute] = $this->getMatches($request, $response);
                if ($matchingRouteGroup) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTING, context: ['route' => $matchingRoute]);
                    if ($matchingRoute && ($template = $matchingRoute->getTemplate($response->getResponseFormat()))) {
                        $response->setTemplate($template);
                    }
                    $result = $matchingRouteGroup->invoke($request, $response) ?? null;
                    HookManager::triggerHook(hookName: HookManager::AFTER_ROUTING, context: ['route' => $matchingRoute, 'result' => $result]);
                }
                if (isset($this->responseMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_RESPONSE_MIDDLEWARE, context: ['response' => $response, 'middleware' => $this->responseMiddleware]);
                    $response = match (($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $matchingRoute)) instanceof ResponseInterface) {
                        true => $responseMiddlewareResult,
                        default => $response
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_RESPONSE_MIDDLEWARE, context: ['response' => $response, 'middleware' => $this->responseMiddleware]);
                }
            } catch (Throwable $exception) {
                foreach ($this->exceptionHandlers as $className => $handler) {
                    if (!$className || ($exception instanceof $className)) {
                        $handlerReflection = new ReflectionFunction($handler);
                        $handlerThis = $handlerReflection->getClosureThis();
                        if (($result = $handler->call($handlerThis, $exception, $request, $response, $matchingRoute)) instanceof ResponseInterface) {
                            $response = $result;
                            return null;
                        }
                        $response->setData($result);
                        return $result;
                    }
                }
                throw $exception;
            }
        } catch (HttpException $exception) {
            $response->setException($exception);
            $response = $response
                ->withStatus($exception->getCode(), $exception->getMessage())
            ;
        }
        return $result;
    }
}
