<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class NullIfEmpty implements ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value): mixed
    {
        return empty($value) ? null : $value;
    }
}