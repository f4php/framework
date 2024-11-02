<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\ValidationFailedException;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsEmail implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return match(\filter_var(value: $value, filter: FILTER_VALIDATE_EMAIL)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not a valid email address"),
            default => $value
        };
    }
}