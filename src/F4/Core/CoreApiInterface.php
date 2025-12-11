<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\Route;
use F4\Core\RouteGroup;
use F4\Core\LocalizerInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\RouterInterface;
use F4\Core\SessionManagerInterface;

interface CoreApiInterface
{
    public function addExceptionHandler(string $exceptionClassName, callable $handler): static;
    public function addHook(string $hookName, callable $callback): static;
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route;
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup;
    public function after(callable $handler): static;
    public function before(callable $handler): static;
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool;
    public function getDebugger(): DebuggerInterface;
    public function getLocalizer(): LocalizerInterface;
    public function getMatchingRoute(): ?Route;
    public function getMatchingRouteGroup(): ?RouteGroup;
    public function getRequest(): RequestInterface;
    public function getResponse(): ResponseInterface;
    public function getResponseFormat(): string;
    public function getRouter(): RouterInterface;
    public function getSessionManager(): SessionManagerInterface;
    public function getTemplate(?string $format = null): string;
    public function log(mixed $value, ?string $description = null): void;
    public function on(string $exceptionClassName, callable $handler): static;
    public function setDebugger(DebuggerInterface $debugger): static;
    public function setLocalizer(LocalizerInterface $localizer): static;
    public function setRequest(RequestInterface $request): static;
    public function setRequestHandler(callable $handler): static;
    public function setResponse(ResponseInterface $response): static;
    public function setResponseFormat(string $format): static;
    public function setResponseHandler(callable $handler): static;
    public function setRouter(RouterInterface $router): static;
    public function setSessionManager(SessionManagerInterface $sessionManager): static;
    public function setTemplate(string $template, ?string $format=null): static;
    public function setTimezone(string $timezone): static;
}
