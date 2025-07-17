<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function is_integer;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsInt implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (is_integer(value: $value)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not an integer")
                ->withContext($context),
            default => $value
        };
    }
}