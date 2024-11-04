<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Filter implements ValidatorAttributeInterface
{
    public function __construct(protected int $flags, protected int|array $options = FILTER_NULL_ON_FAILURE) {}
    public function getFilteredValue(mixed $value): mixed
    {
        return match(null === $value) {
            true => null,
            default => filter_var(value: $value, filter: $this->flags, options: $this->options & FILTER_NULL_ON_FAILURE)
        };
    }
}