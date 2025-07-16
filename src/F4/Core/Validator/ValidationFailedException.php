<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use InvalidArgumentException;
class ValidationFailedException extends InvalidArgumentException
{
    protected string $argumentName;
    protected string $argumentType;

    public function setArgumentName(string $argumentName): static
    {
        $this->argumentName = $argumentName;
        return $this;
    }
    public function setArgumentType(string $argumentType): static
    {
        $this->argumentType = $argumentType;
        return $this;
    }
    public function getArgumentName(): string
    {
        return $this->argumentName;
    }
    public function getArgumentType(): string
    {
        return $this->argumentType;
    }
}