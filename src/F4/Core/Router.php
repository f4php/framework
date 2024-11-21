<?php

declare(strict_types=1);

namespace F4\Core;

use ErrorException;
use Throwable;

use F4\Core\Exception\HttpException;
use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;

use F4\Core\RouterInterface;

class Router implements RouterInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;

    protected array $routeGroups = [];

    public function __construct() {
        /**
         * This is the default group, all ungrouped routes end up here
         */
        $this->routeGroups[0] = new RouteGroup();
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
        $matchingGroupsData = \array_reduce($this->routeGroups, function ($result, RouteGroup $routeGroup) use ($request, $response) {
            return match($routes = $routeGroup->getMatchingRoutes(request: $request, response: $response)) {
                [] => $result,
                default => [...$result, [
                    'routeGroup' => $routeGroup,
                    'routes' => $routes
                ]]
            };
        }, []);
        if(!$policyCheckFunction($matchingGroupsData)) {
            throw new ErrorException(message: 'Routing policy check failed');
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
        $policyCheckFunction = function(array $matchingGroupsData): bool {
            return (\count($matchingGroupsData ?? []) <= 1) && (\count($matchingGroupsData[0]['routes'] ?? []) <= 1);
        };
        [$matchingRouteGroup, $matchingRoute] = $this->getMatchesUsingPolicy($request, $response, $policyCheckFunction);
        try {
            try {
                if(isset($this->requestMiddleware)) {
                    $request = match(($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $matchingRoute)) instanceof RequestInterface) { 
                        true => $requestMiddlewareResult,
                        default => $request
                    };
                }
                /**
                 * Need to match again in case the Request was altered by RequestMiddleware
                 */
                [$matchingRouteGroup, $matchingRoute] = $this->getMatchesUsingPolicy($request, $response, $policyCheckFunction);
                if ($matchingRouteGroup) {
                    $result = $matchingRouteGroup->invoke($request, $response)[0] ?? null;
                    if($template = $matchingRoute->getTemplate($response->getResponseFormat())) {
                        $response->setTemplate($template);
                    }
                }
                if(isset($this->responseMiddleware)) {
                    $response = match(($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $matchingRoute)) instanceof ResponseInterface) { 
                        true => $responseMiddlewareResult,
                        default => $response
                    };
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
