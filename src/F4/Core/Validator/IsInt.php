<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\ValidationFailedException;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsInt implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return match(\is_integer(value: $value)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not an integer"),
            default => $value
        };
    }
}