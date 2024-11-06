<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsGreaterThan implements ValidatorAttributeInterface
{
    public function __construct(protected int $target)
    {
    }
    public function getFilteredValue(mixed $value): mixed
    {
        return match (\is_numeric(value: $value) && ($value > $this->target)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not an integer"),
            default => $value
        };
    }
}