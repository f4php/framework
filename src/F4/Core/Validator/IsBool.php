<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function is_bool;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsBool implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (is_bool(value: $value)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not a valid boolean")
                ->withContext($context),
            default => $value
        };
    }
}