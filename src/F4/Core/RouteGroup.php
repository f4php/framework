<?php

declare(strict_types=1);

namespace F4\Core;

use Throwable;

use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\PriorityAwareTrait;

use function array_filter;
use function array_map;
use function array_reduce;

class RouteGroup implements RouteGroupInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;
    use PriorityAwareTrait;

    protected array $routes = [];
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->routes[] = match ($routeOrPath instanceof Route) {
            true => $routeOrPath,
            default => new Route(pathDefinition: $routeOrPath, handler: $handler)
        };
    }
    public function addRoutes(...$routes): static
    {
        $routes = array_reduce($routes, function($result, $route) {
            return [...$result, ...match(\is_array($route)) {
                true => [...$route],
                false => [$route]
            }];
        }, []);
        (function (Route ...$routes): void{})(...$routes);
        array_map(callback: function($route) {
            $this->addRoute($route);
        }, array: $routes);
        return $this;
    }
    static public function withRoutes(...$routes): static
    {
        return (new self())->addRoutes(...$routes);
    }
    static public function fromRoutes(...$routes): static
    {
        return self::withRoutes(...$routes);
    }
    public function getRoutes(): array {
        return $this->routes;
    }
    public function hasMatchingRoutes(RequestInterface $request, ResponseInterface $response): bool
    {
        return array_reduce($this->routes,function ($result, Route $route) use ($request, $response): bool {
            return $result || $route->checkMatch(request: $request, response: $response);
        }, false);
    }
    public function getMatchingRoutes(RequestInterface $request, ResponseInterface $response): array
    {
        $routes = array_filter(array: $this->routes, callback: function (Route $route) use ($request, $response): bool {
            return $route->checkMatch(request: $request, response: $response);
        });
        \usort(array: $routes, callback: function (Route $routeA, Route $routeB): int {
            return (int) $routeB->getPriority() - (int) $routeA->getPriority();
        });
        return $routes;
    }
    public function invoke(RequestInterface &$request, ResponseInterface &$response): mixed {
        $result = [];
        if($matchingRoutes = $this->getMatchingRoutes(request: $request, response: $response)) {
            foreach($matchingRoutes as $route) {
                try {
                    if(isset($this->requestMiddleware)) {
                        HookManager::triggerHook(hookName: HookManager::BEFORE_REQUEST_MIDDLEWARE, context: ['request'=>$request, 'routeGroup'=>$this]);
                        $request = match(($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $route)) instanceof RequestInterface) {
                            true => $requestMiddlewareResult,
                            default => $request
                        };
                        HookManager::triggerHook(hookName: HookManager::AFTER_REQUEST_MIDDLEWARE, context: ['request'=>$request, 'routeGroup'=>$this]);
                    }
                    HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_GROUP, context: ['routeGroup'=>$this]);
                    $result[] = $route->invoke($request, $response);
                    HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_GROUP, context: ['routeGroup'=>$this]);
                    if(isset($this->responseMiddleware)) {
                        HookManager::triggerHook(hookName: HookManager::BEFORE_RESPONSE_MIDDLEWARE, context: ['response'=>$response, 'routeGroup'=>$this]);
                        $response = match(($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $route)) instanceof ResponseInterface) {
                            true => $responseMiddlewareResult,
                            default => $response
                        };
                        HookManager::triggerHook(hookName: HookManager::AFTER_RESPONSE_MIDDLEWARE, context: ['response'=>$response, 'routeGroup'=>$this]);
                    }
                }
                catch (Throwable $exception) {
                    $handled = false;
                    foreach ($this->exceptionHandlers as $className => $handler) {
                        if (!$className || ($exception instanceof $className)) {
                            $result[] = $handler->call($this, $exception, $request, $response, $route);
                            $handled = true;
                            break;
                        }
                    }
                    if(!$handled) {
                        throw $exception;
                    }
                }
            }
        }
        return $result;
    }

}
