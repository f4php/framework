<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;

interface RouterInterface
{
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route;
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup;
    public function invokeMatchingRoutes(RequestInterface &$request, ResponseInterface &$response): mixed;
    public function setRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static;
    public function before(RequestMiddleware|callable $requestMiddleware): static;
    public function setResponseMiddleware(ResponseMiddleware|callable $responseMiddleware): static;
    public function after(ResponseMiddleware|callable $responseMiddleware): static;
    public function addExceptionHandler(string $exceptionClassName, callable $exceptionHandler): static;
    public function on(string $exceptionClassName, callable $exceptionHandler): static;
    public function getExceptionHandlers(?string $exceptionClass = null): array;

}