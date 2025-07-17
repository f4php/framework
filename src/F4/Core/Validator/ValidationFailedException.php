<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidationContextInterface;
use InvalidArgumentException;
class ValidationFailedException extends InvalidArgumentException
{
    protected ?string $argumentName = null;
    protected ?string $argumentType = null;
    protected mixed $argumentValue = null;
    protected ?ValidationContextInterface $context = null;
    public function getArgumentName(): string
    {
        return $this->argumentName;
    }
    public function getArgumentType(): string
    {
        return $this->argumentType;
    }
    public function getArgumentValue(): mixed
    {
        return $this->argumentValue;
    }
    public function getContext(): mixed
    {
        return $this->context;
    }
    public function withArgumentName(string $argumentName): static
    {
        $this->argumentName = $argumentName;
        return $this;
    }
    public function withArgumentType(string $argumentType): static
    {
        $this->argumentType = $argumentType;
        return $this;
    }
    public function withArgumentValue(mixed $argumentValue): static
    {
        $this->argumentValue = $argumentValue;
        return $this;
    }
    public function withContext(ValidationContextInterface $context): static
    {
        $this->context = $context;
        return $this;
    }
}