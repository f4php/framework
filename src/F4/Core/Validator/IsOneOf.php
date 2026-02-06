<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function in_array;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsOneOf implements ValidatorAttributeInterface
{
    public function __construct(protected readonly array $values) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (in_array(needle: $value, haystack: $this->values, strict: true)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not one of the required values")
                ->withContext($context),
            default => $value
        };
    }
}