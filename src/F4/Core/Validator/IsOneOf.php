<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsOneOf implements ValidatorAttributeInterface
{
    public function __construct(protected array $values) {}
    public function getFilteredValue(mixed $value): mixed
    {
        return match (\in_array(needle: $value, haystack: $this->values)) {
            false => throw new ValidationFailedException(message: "{$value} is not within range"),
            default => $value
        };
    }
}