<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\AbstractMiddleware;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

class ResponseMiddleware extends AbstractMiddleware
{
    public function invoke(ResponseInterface $response, RequestInterface $request, mixed $context): mixed
    {
        return match($context) {
            null => ($this->handler)($response, $request, null),
            default => $this->handler->call($context, $response, $request, $context),
        };
    }
}