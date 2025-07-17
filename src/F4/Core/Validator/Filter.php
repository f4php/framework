<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function filter_var;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Filter implements ValidatorAttributeInterface
{
    public function __construct(protected int $filter, protected int|array $options = FILTER_NULL_ON_FAILURE) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (null === $value) {
            true => null,
            default => filter_var(value: $value, filter: $this->filter, options: $this->options & FILTER_NULL_ON_FAILURE)
        };
    }
}