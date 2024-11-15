<?php

declare(strict_types=1);

namespace F4\Core;

use InvalidArgumentException;
use F4\Core\RequestInterface;
use F4\Core\RequestMiddleware;
use F4\Core\ResponseInterface;
use F4\Core\ResponseMiddleware;

trait MiddlewareAwareTrait
{
    protected RequestMiddleware $requestMiddleware;
    protected ResponseMiddleware $responseMiddleware;

    public function setRequestMiddleware(RequestMiddleware|callable $requestMiddleware): static
    {
        if (isset($this->requestMiddleware)) {
            throw new InvalidArgumentException(message: 'Request middleware already set');
        }
        $this->requestMiddleware = match ($requestMiddleware instanceof RequestMiddleware) {
            true => $requestMiddleware,
            default => new RequestMiddleware(handler: $requestMiddleware),
        };
        return $this;
    }
    public function before(RequestMiddleware|callable $requestMiddleware): static
    {
        return $this->setRequestMiddleware($requestMiddleware);
    }
    public function setResponseMiddleware(ResponseMiddleware|callable $responseMiddleware): static
    {
        if (isset($this->responseMiddleware)) {
            throw new InvalidArgumentException(message: 'Response middleware already set');
        }
        $this->responseMiddleware = match ($responseMiddleware instanceof ResponseMiddlware) {
            true => $responseMiddleware,
            default => new ResponseMiddleware(handler: $responseMiddleware),
        };
        return $this;
    }
    public function after(ResponseMiddleware|callable $responseMiddleware): static
    {
        return $this->setResponseMiddleware($responseMiddleware);
    }
    public function invokeRequestMiddleware(RequestInterface $request, ResponseInterface $response, mixed $context = null): mixed
    {
        return $this->requestMiddleware->invoke(request: $request, response: $response, context: $context ?? $this);
    }
    public function invokeResponseMiddleware(ResponseInterface $response, RequestInterface $request, mixed $context = null): mixed
    {
        return $this->responseMiddleware->invoke(response: $response, request: $request, context: $context ?? $this);
    }
    protected function getRequestMiddleware(): RequestMiddleware
    {
        return $this->requestMiddleware;
    }
    protected function getResponseMiddleware(): ResponseMiddleware
    {
        return $this->responseMiddleware;
    }
}