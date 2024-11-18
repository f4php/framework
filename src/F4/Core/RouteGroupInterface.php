<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\RequestInterface;
use F4\Core\RequestMiddleware;
use F4\Core\ResponseInterface;
use F4\Core\ResponseMiddleware;

interface RouteGroupInterface
{
    public function setRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static;
    public function before(RequestMiddleware|callable $requestMiddleware): static;
    public function setEachRouteRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static;
    public function beforeEach(RequestMiddleware|callable $requestMiddleware): static;
    public function setResponseMiddleware(ResponseMiddleware|callable $requestMiddleware): static;
    public function after(ResponseMiddleware|callable $requestMiddleware): static;
    public function setEachRouteResponseMiddleware(ResponseMiddleware|callable $requestMiddleware): static;
    public function afterEach(ResponseMiddleware|callable $responseMiddleware): static;
    public function addExceptionHandler(string $exceptionClassName, callable $exceptionHandler): static;
    public function on(string $exceptionClassName, callable $exceptionHandler): static;
    public function getExceptionHandlers(?string $exceptionClass = null): array;
    public function invoke(RequestInterface &$request, ResponseInterface &$response): mixed;
    public function addRoutes(...$routes): static;
    static public function withRoutes(...$routes): static;
    static public function fromRoutes(...$routes): static;

}
