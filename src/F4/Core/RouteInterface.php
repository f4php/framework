<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;
use F4\Config;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

interface RouteInterface
{
    public function __construct(string $pathDefinition, callable $handler);
    public function setTemplate(string $template, ?string $format = Config::DEFAULT_RESPONSE_FORMAT): static;
    public function getTemplate(string $format = Config::DEFAULT_RESPONSE_FORMAT): ?string;
    public function setName($name): static;
    public function getName(): string|null;
    public function getHandler(): Closure;
    public function getRequestPathRegExp(): string;
    public function checkMatch(RequestInterface $request, ResponseInterface $response, ?string $pathPrefix = null): bool;
    public function invoke(RequestInterface &$request, ResponseInterface &$response, ?string $pathPrefix = null): mixed;
    public function setRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static;
    public function before(RequestMiddleware|callable $requestMiddleware): static;
    public function setResponseMiddleware(ResponseMiddleware|callable $responseMiddleware): static;
    public function after(ResponseMiddleware|callable $responseMiddleware): static;
    public function addExceptionHandler(string $exceptionClassName, callable $exceptionHandler): static;
    public function on(string $exceptionClassName, callable $exceptionHandler): static;
    public function getExceptionHandlers(?string $exceptionClass = null): array;
    static public function get(string $pathDefinition, callable $handler): static;
    static public function head(string $pathDefinition, callable $handler): static;
    static public function post(string $pathDefinition, callable $handler): static;
    static public function put(string $pathDefinition, callable $handler): static;
    static public function delete(string $pathDefinition, callable $handler): static;
    static public function connect(string $pathDefinition, callable $handler): static;
    static public function options(string $pathDefinition, callable $handler): static;
    static public function trace(string $pathDefinition, callable $handler): static;
    static public function any(string $pathDefinition, callable $handler): static;
}
