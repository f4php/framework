<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastInt implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value): mixed
    {
        return (int) $value;
    }
}