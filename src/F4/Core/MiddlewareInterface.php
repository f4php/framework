<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;

interface MiddlewareInterface
{
    public function getHandler(): Closure;
}