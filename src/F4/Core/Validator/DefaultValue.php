<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\WithDefaultTrait;

#[Attribute(Attribute::TARGET_PARAMETER)]
class DefaultValue implements ValidatorAttributeInterface
{
    use WithDefaultTrait;
    public function __construct(mixed $defaultValue) {
        $this->defaultValue = $defaultValue;
    }
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return $value ?? $this->defaultValue ?? $defaultValue;
    }
}