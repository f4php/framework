<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Min implements ValidatorAttributeInterface
{
    public function __construct(protected int $min) {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return \max($value, $this->min);
    }
}