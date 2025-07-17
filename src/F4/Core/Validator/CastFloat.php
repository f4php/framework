<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastFloat implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return (float) $value;
    }
}