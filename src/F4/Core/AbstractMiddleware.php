<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;

class AbstractMiddleware
{
    protected Closure $handler;
    public function __construct(callable $handler)
    {
        $this->handler = $handler(...);
    }

    public function getHandler(): Closure {
        return $this->handler;
    }
}