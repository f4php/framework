<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core;
use F4\Core\CoreApiInterface;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;
use F4\Core\Route;
use F4\Core\RouterInterface;

class CoreApiProxy implements CoreApiInterface
{
    public function __construct(protected Core $core) {}
    public function addRoute(Route|string $routeOrPath, ?callable $handler = null): Route
    {
        return $this->core->addRoute(routeOrPath: $routeOrPath, handler: $handler);
    }
    public function addRouteGroup(RouteGroup $routeGroup): RouteGroup
    {
        return $this->core->addRouteGroup($routeGroup);
    }
    public function setResponseFormat(string $format): static
    {
        $this->core->setResponseFormat(format: $format);
        return $this;
    }
    public function getDebugger(): DebuggerInterface
    {
        return $this->core->getDebugger();
    }
    public function setDebugger(DebuggerInterface $debugger): static
    {
        $this->core->setDebugger(debugger: $debugger);
        return $this;
    }
    public function getRouter(): RouterInterface
    {
        return $this->core->getRouter();
    }
    public function getMatchingRoute(): ?Route
    {
        return $this->core->getMatchingRoute();
    }
    public function getMatchingRouteGroup(): ?RouteGroup
    {
        return $this->core->getMatchingRouteGroup();
    }
    public function setRouter(RouterInterface $router): static
    {
        $this->core->setRouter(router: $router);
        return $this;
    }
    public function addHook(string $hookName, callable $callback): static
    {
        $this->core->addHook(hookName: $hookName, callback: $callback);
        return $this;
    }
    public function getResponseFormat(): string
    {
        return $this->core->getResponseFormat();
    }
    public function setTemplate(string $template, ?string $format = null): static
    {
        $this->core->setTemplate(template: $template, format: $format);
        return $this;
    }
    public function getTemplate(?string $format = null): string
    {
        return $this->core->getTemplate(format: $format);
    }
    public function setTimezone(string $timezone): static
    {
        $this->core->setTimezone(timezone: $timezone);
        return $this;
    }
    public function setRequestHandler(callable $handler): static
    {
        $this->core->setRequestHandler(handler: $handler);
        return $this;
    }
    public function before(callable $handler): static
    {
        return $this->setRequestHandler(handler: $handler);
    }
    public function setResponseHandler(callable $handler): static
    {
        $this->core->setResponseHandler(handler: $handler);
        return $this;
    }
    public function after(callable $handler): static
    {
        return $this->setResponseHandler(handler: $handler);
    }
    public function addExceptionHandler(string $exceptionClassName, callable $handler): static
    {
        $this->core->addExceptionHandler(exceptionClassName: $exceptionClassName, handler: $handler);
        return $this;
    }
    public function on(string $exceptionClassName, callable $handler): static
    {
        return $this->addExceptionHandler(exceptionClassName: $exceptionClassName, handler: $handler);
    }
    public function setRequest(RequestInterface $request): static
    {
        $this->core->setRequest($request);
        return $this;
    }
    public function setResponse(ResponseInterface $response): static
    {
        $this->core->setResponse($response);
        return $this;
    }
    public function getRequest(): RequestInterface
    {
        return $this->core->getRequest();
    }
    public function getResponse(): ResponseInterface
    {
        return $this->core->getResponse();
    }
    public function emit(ResponseInterface $response, ?RequestInterface $request = null): bool
    {
        return $this->core->emit($response, $request);
    }
    public function log(mixed $value, ?string $description = null): void
    {
        $this->core->log($value, $description);
    }

}
