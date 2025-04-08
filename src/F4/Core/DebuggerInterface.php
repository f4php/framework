<?php

declare(strict_types=1);

namespace F4\Core;

use F4\Core\RequestInterface;

interface DebuggerInterface
{
    public function checkIfEnabledByRequest(RequestInterface $request): bool;
    public function captureAndEmit(callable $emitCallback): bool;
    public function log(mixed $value, ?string $description = null): void;
}
