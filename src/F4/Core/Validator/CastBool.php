<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastBool implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return (bool)filter_var(value: $value, filter: FILTER_VALIDATE_BOOLEAN);
    }
}