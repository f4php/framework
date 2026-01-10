<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class UnsafeString implements ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return (string) $value;
    }
}