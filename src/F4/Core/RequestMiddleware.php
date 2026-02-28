<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\{
    AbstractMiddleware,
    RequestInterface,
    ResponseInterface,
};

class RequestMiddleware extends AbstractMiddleware
{
    public function invoke(RequestInterface $request, ResponseInterface $response, mixed $context): mixed
    {
        return ($this->handler)($request, $response, $context ?: null);
    }
}