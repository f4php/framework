<?php

declare(strict_types=1);

namespace F4\Core;

use ReflectionFunction;

use F4\Core\{
    AbstractMiddleware,
    RequestInterface,
    ResponseInterface,
};

class RequestMiddleware extends AbstractMiddleware
{
    public function invoke(RequestInterface $request, ResponseInterface $response, mixed $context): mixed
    {
        $handlerReflection = new ReflectionFunction($this->handler);
        $handlerThis = $handlerReflection->getClosureThis();
        return $this->handler->call($handlerThis, $request, $response, $context ?: null);
    }
}