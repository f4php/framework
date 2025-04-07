<?php

declare(strict_types=1);

namespace F4\Core;

use Throwable;
use ErrorException;

use F4\HookManager;
use F4\Core\ExceptionHandlerTrait;
use F4\Core\MiddlewareAwareTrait;
use F4\Core\Route;

use function array_find;
use function array_map;
use function array_reduce;

class RouteGroup implements RouteGroupInterface
{
    use ExceptionHandlerTrait;
    use MiddlewareAwareTrait;

    protected string $pathPrefix = '';
    protected array $routes = [];

    public function __construct(string $pathPrefix='', array $routes=[]) {
        $this->setPathPrefix($pathPrefix);
        $this->addRoutes($routes);
    }
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
    public static function withRoutes(...$routes): static
    {
        return (new self())->addRoutes(...$routes);
    }
    public static function fromRoutes(...$routes): static
    {
        return self::withRoutes(...$routes);
    }
    public function getRoutes(): array {
        return $this->routes;
    }
    public function setPathPrefix(string $pathPrefix): static {
        $this->pathPrefix = $pathPrefix;
        return $this;
    }
    public function getPathPrefix(): string {
        return $this->pathPrefix;
    }
    public function hasMatchingRoute(RequestInterface $request, ResponseInterface $response): bool
    {
        return array_reduce($this->routes,function ($result, Route $route) use ($request, $response): bool {
            return $result || $route->checkMatch(request: $request, response: $response, pathPrefix: $this->pathPrefix);
        }, false);
    }
    public function getMatchingRoute(RequestInterface $request, ResponseInterface $response): ?Route
    {
        return array_find(array: $this->routes, callback: function (Route $route) use ($request, $response): bool {
            return $route->checkMatch(request: $request, response: $response, pathPrefix: $this->pathPrefix);
        });
    }
    public function invoke(RequestInterface &$request, ResponseInterface &$response): mixed {
        $result = null;
        if($route = $this->getMatchingRoute(request: $request, response: $response)) {
            try {
                if(isset($this->requestMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_GROUP_REQUEST_MIDDLEWARE, context: ['request'=>$request, 'routeGroup'=>$this, 'middleware'=>$this->requestMiddleware]);
                    $request = match(($requestMiddlewareResult = $this->invokeRequestMiddleware(request: $request, response: $response, context: $this)) instanceof RequestInterface) {
                        true => $requestMiddlewareResult,
                        default => $request
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_GROUP_REQUEST_MIDDLEWARE, context: ['request'=>$request, 'routeGroup'=>$this, 'middleware'=>$this->requestMiddleware]);
                }
                HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_GROUP, context: ['routeGroup'=>$this]);
                $result = $route->invoke($request, $response);
                HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_GROUP, context: ['routeGroup'=>$this]);
                if(isset($this->responseMiddleware)) {
                    HookManager::triggerHook(hookName: HookManager::BEFORE_ROUTE_GROUP_RESPONSE_MIDDLEWARE, context: ['response'=>$response, 'routeGroup'=>$this, 'middleware'=>$this->responseMiddleware]);
                    $response = match(($responseMiddlewareResult = $this->invokeResponseMiddleware(response: $response, request: $request, context: $this)) instanceof ResponseInterface) {
                        true => $responseMiddlewareResult,
                        default => $response
                    };
                    HookManager::triggerHook(hookName: HookManager::AFTER_ROUTE_GROUP_RESPONSE_MIDDLEWARE, context: ['response'=>$response, 'routeGroup'=>$this, 'middleware'=>$this->responseMiddleware]);
                }
            }
            catch (Throwable $exception) {
                foreach ($this->exceptionHandlers as $className => $handler) {
                    if (!$className || ($exception instanceof $className)) {
                        if(($result = $handler->call($this, $exception, $request, $response, $route)) instanceof ResponseInterface) {
                            $response = $result;
                            return null;
                        }
                        $response->setData($result);
                        return $result;
                    }
                }
                throw $exception;
            }
        }
        return $result;
    }

}
