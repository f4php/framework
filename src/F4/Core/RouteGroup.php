<?php

declare(strict_types=1);

namespace F4\Core;

use InvalidArgumentException;
use Throwable;

use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\PriorityAwareTrait;

class RouteGroup implements RouteGroupInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;
    use PriorityAwareTrait;

    protected RequestMiddleware $eachRouteRequestMiddleware;
    protected ResponseMiddleware $eachRouteResponseMiddleware;
    protected array $routes = [];
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->routes[] = match ($routeOrPath instanceof Route) {
            true => $routeOrPath,
            default => new Route(pathDefinition: $routeOrPath, handler: $handler)
        };
    }
    public function addRoutes(array $routes): self
    {
        (function (Route ...$routes): void{})(...$routes);
        \array_map(callback: function($route) {
            $this->addRoute($route);
        }, array: $routes);
        return $this;
    }
    public function getRoutes(): array {
        return $this->routes;
    }
    public function setEachRouteRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static {
        if (isset($this->eachRouteRequestMiddleware)) {
            throw new InvalidArgumentException(message: 'Each route request middleware already set');
        }
        $this->eachRouteRequestMiddleware = $requestMiddleware;
        return $this;
    }

    public function beforeEach(RequestMiddleware|callable $requestMiddleware): static {
        return $this->setEachRouteRequestMiddleware($requestMiddleware);
    }

    public function setEachRouteResponseMiddleware(ResponseMiddleware|callable $responseMiddleware): static {
        if (isset($this->eachRouteResponseMiddleware)) {
            throw new InvalidArgumentException(message: 'Each route response middleware already set');
        }
        $this->eachRouteResponseMiddleware = $responseMiddleware;
        return $this;
    }
    public function afterEach(ResponseMiddleware|callable $responseMiddleware): static {
        return $this->setEachRouteResponseMiddleware($responseMiddleware);
    }

    protected function getMatchingRoutes(RequestInterface $request, ResponseInterface $response): array
    {
        $method = $request->getMethod();
        $path = $request->getPath();
        $responseFormat = $response->getResponseFormat();
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
            try {
                if(isset($this->requestMiddleware)) {
                    $this->invokeRequestMiddleware(request: $request, response: $response);
                }
                foreach($matchingRoutes as $route) {
                    if(isset($this->eachRouteRequestMiddleware)) {
                        $this->eachRouteRequestMiddleware->invoke(request: $request, response: $response, context: $route);
                    }
                    $result[] = $route->invoke($request, $response);
                    if(isset($this->eachRouteResponseMiddleware)) {
                        $this->eachRouteResponseMiddleware->invoke(response: $response, request: $request, context: $route);
                    }
                }
                if(isset($this->responseMiddleware)) {
                    $this->invokeResponseMiddleware(response: $response, request: $request);
                }
            }
            catch (Throwable $exception) {
                $handled = false;
                foreach ($this->exceptionHandlers as $className => $handler) {
                    if (!$className || ($exception instanceof $className)) {
                        $result[] = $handler->call($this, $exception, $request, $response);
                        $handled = true;
                    }
                }
                if(!$handled) {
                    throw $exception;
                }
            }
        }
        return $result;
    }

}
