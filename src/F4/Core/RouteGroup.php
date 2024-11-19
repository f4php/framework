<?php

declare(strict_types=1);

namespace F4\Core;

use Throwable;

use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\PriorityAwareTrait;

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
        $routes = \array_reduce($routes, function($result, $route) {
            return [...$result, ...match(\is_array($route)) {
                true => [...$route],
                false => [$route]
            }];
        }, []);
        (function (Route ...$routes): void{})(...$routes);
        \array_map(callback: function($route) {
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
        return \array_reduce($this->routes,function ($result, Route $route) use ($request, $response): bool {
            return $result || $route->checkMatch(request: $request, response: $response);
        }, false);
    }
    public function getMatchingRoutes(RequestInterface $request, ResponseInterface $response): array
    {
        $routes = \array_filter(array: $this->routes, callback: function (Route $route) use ($request, $response): bool {
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
            foreach($matchingRoutes as $index=>$route) {
                try {
                    if(isset($this->requestMiddleware)) {
                        $request = match(($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $route)) instanceof RequestInterface) {
                            true => $requestMiddlewareResult,
                            default => $request
                        };
                    }
                    $result[] = $route->invoke($request, $response);
                    if(isset($this->responseMiddleware)) {
                        $response = match(($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $route)) instanceof ResponseInterface) {
                            true => $responseMiddlewareResult,
                            default => $response
                        };
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
