<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\ValidationFailedException;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsOneOf implements ValidatorAttributeInterface
{
    public function __construct(protected array $values) {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return match (\in_array(needle: $value, haystack: $this->values)) {
            true => $value,
            default => throw new ValidationFailedException(message: "{$value} is not within range"),
        };
    }
}