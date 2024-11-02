<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Filter implements ValidatorAttributeInterface
{
    public function __construct(protected int $flags, protected int|array $options = 0) {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return filter_var(value: $value, filter: $this->flags, options: $this->options);
    }
}