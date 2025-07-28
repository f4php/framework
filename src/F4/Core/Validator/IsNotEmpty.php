<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsNotEmpty implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match(empty($value)) {
            true => throw new ValidationFailedException(message: "Empty parameter value is not allowed")
                ->withContext($context),
            default => $value
        };
    }
}