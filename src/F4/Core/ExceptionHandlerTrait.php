<?php

declare(strict_types=1);

namespace F4\Core;

use InvalidArgumentException;
use Throwable;

trait ExceptionHandlerTrait
{
    protected array $exceptionHandlers = [];

    public function addExceptionHandler(string $exceptionClassName, callable $exceptionHandler): static
    {
        if (!\is_subclass_of(object_or_class: $exceptionClassName, class: Throwable::class)) {
            throw new InvalidArgumentException(message: '${exceptionClassName} is not throwable');
        }
        if (isset($this->exceptionHandlers[$exceptionClassName])) {
            throw new InvalidArgumentException(message: '${exceptionClassName} handler is already set');
        }
        $this->exceptionHandlers[$exceptionClassName] = $exceptionHandler(...);
        return $this;
    }
    public function on(string $exceptionClassName, callable $exceptionHandler): static
    {
        return $this->addExceptionHandler(exceptionClassName: $exceptionClassName, exceptionHandler: $exceptionHandler);
    }

    public function processException(Throwable $exception, ...$arguments): mixed
    {
        foreach ($this->exceptionHandlers as $className => $handlers) {
            if ($exception instanceof $className) {
                foreach ($handlers as $handler) {
                    return $handler->call($this, $exception, ...$arguments);
                }
            }
        }
        throw $exception;
    }
    public function getExceptionHandlers(?string $exceptionClass = null): array
    {
        return match (null !== $exceptionClass) {
            true => $this->exceptionHandlers[$exceptionClass] ?? null,
            default => $this->exceptionHandlers
        };
    }
}