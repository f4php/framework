<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\Route;
use F4\Core\RouteGroup;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\RouterInterface;
use F4\ModuleInterface;

interface CoreApiInterface
{
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route;
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup;
    public function setResponseFormat(string $format): static;
    public function getResponseFormat(): string;
    public function setTemplate(string $template, ?string $format=null): static;
    public function getTemplate(?string $format = null): string;
    public function setTimezone(string $timezone): static;
    public function setRequestHandler(callable $handler): static;
    public function before(callable $handler): static;
    public function setResponseHandler(callable $handler): static;
    public function after(callable $handler): static;
    public function addExceptionHandler(string $exceptionClassName, callable $handler): static;
    public function on(string $exceptionClassName, callable $handler): static;
    public function setRouter(RouterInterface $router): static;
    public function getRouter(): RouterInterface;
    public function setRequest(RequestInterface $request): static;
    public function getRequest(): RequestInterface;
    public function setResponse(ResponseInterface $response): static;
    public function getResponse(): ResponseInterface;
    public function emit(?ResponseInterface $response = null): bool;
}
