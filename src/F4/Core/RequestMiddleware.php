<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\AbstractMiddleware;
use F4\Core\RequestInterface;
use F4\Core\ResponseInterface;

class RequestMiddleware extends AbstractMiddleware
{
    public function invoke(RequestInterface $request, ResponseInterface $response, mixed $context): mixed
    {
        return $this->handler->call($context, $request, $response);
    }
}