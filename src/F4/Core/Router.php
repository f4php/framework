<?php

declare(strict_types=1);

namespace F4\Core;

use ErrorException;
use InvalidArgumentException;
use Throwable;

use F4\Core\CoreApiInterface;
use F4\Core\Exception\HttpException;
use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;

use F4\Core\RouterInterface;

use function array_reduce;
use function is_callable;
use function count;

class Router implements RouterInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;

    protected array $routeGroups = [];
    protected mixed $policyCheckFunction {
        set(mixed $function) {
            if(!is_callable(value: $function)) {
                throw new InvalidArgumentException(message: "Policy function must be callable");
            }
            $this->policyCheckFunction = $function;
        }
    }
    public function __construct(?callable $policyCheckFunction = null) {
        /**
         * This is the default group, all ungrouped routes end up here
         */
        $this->routeGroups[0] = new RouteGroup();
        $this->policyCheckFunction = $policyCheckFunction ?: function(array $matchingGroupsData): bool {
            return (count($matchingGroupsData) <= 1) && (count($matchingGroupsData[0]['routes'] ?? []) <= 1);
        };
    }
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup {
        return $this->routeGroups[] = $routeGroup;
    }
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->routeGroups[0]->addRoute(match ($routeOrPath instanceof Route) {
            true => $routeOrPath,
            default => new Route(pathDefinition: $routeOrPath, handler: $handler)
        });
    }
    protected function getMatchesUsingPolicy(RequestInterface $request, ResponseInterface $response, callable $policyCheckFunction): array {
        $matchingGroupsData = array_reduce($this->routeGroups, function ($result, RouteGroup $routeGroup) use ($request, $response) {
            return match($routes = $routeGroup->getMatchingRoutes(request: $request, response: $response)) {
                [] => $result,
                default => [...$result, [
                    'routeGroup' => $routeGroup,
                    'routes' => $routes
                ]]
            };
        }, []);
        if(!$policyCheckFunction($matchingGroupsData)) {
            throw new ErrorException(message: 'Routing policy check failed (by default, multiple route matches are not supported)');
        }
        return [
            $matchingGroupsData[0]['routeGroup'] ?? null,
            $matchingGroupsData[0]['routes'][0] ?? null
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
        [$matchingRouteGroup, $matchingRoute] = $this->getMatchesUsingPolicy($request, $response, $this->policyCheckFunction);
        try {
            try {
                if(isset($this->requestMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_REQUEST_MIDDLEWARE, context: ['request'=>$request]);
                    $request = match(($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $matchingRoute)) instanceof RequestInterface) { 
                        true => $requestMiddlewareResult,
                        default => $request
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_REQUEST_MIDDLEWARE, context: ['request'=>$request]);
                }
                /**
                 * Need to match again in case the Request was altered by RequestMiddleware
                 */
                [$matchingRouteGroup, $matchingRoute] = $this->getMatchesUsingPolicy($request, $response, $this->policyCheckFunction);
                if ($matchingRouteGroup) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTING, context: ['route'=>$matchingRoute]);
                    $result = $matchingRouteGroup->invoke($request, $response)[0] ?? null;
                    if($template = $matchingRoute->getTemplate($response->getResponseFormat())) {
                        $response->setTemplate($template);
                    }
                    HookManager::triggerHook(hookName: HookManager::AFTER_ROUTING, context: ['route'=>$matchingRoute, 'result'=>$result]);
                }
                if(isset($this->responseMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_RESPONSE_MIDDLEWARE, context: ['response'=>$response]);
                    $response = match(($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $matchingRoute)) instanceof ResponseInterface) { 
                        true => $responseMiddlewareResult,
                        default => $response
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_RESPONSE_MIDDLEWARE, context: ['response'=>$response]);
                }
            }
            catch (Throwable $exception) {
                foreach ($this->exceptionHandlers as $className => $handler) {
                    if (!$className || ($exception instanceof $className)) {
                        return $handler->call($this, $exception, $request, $response, $matchingRoute);
                    }
                }
                throw $exception;
            }
        }
        catch (HttpException $exception) {
            $response->setException($exception);
        }
        return $result;
    }
}
