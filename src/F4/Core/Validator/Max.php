<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Max implements ValidatorAttributeInterface
{
    public function __construct(protected int $max)
    {
    }
    public function getFilteredValue(mixed $value): mixed
    {
        return \min($value, $this->max);
    }
}