<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function filter_var;
use function is_array;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Filter implements ValidatorAttributeInterface
{
    public function __construct(protected readonly int $filter, protected readonly int|array $options = FILTER_NULL_ON_FAILURE) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (null === $value) {
            true => null,
            default => filter_var(
                value: $value,
                filter: $this->filter,
                options: is_array($this->options) ? [
                    ...$this->options,
                    'flags' => [
                        ...$this->options['flags']??[],
                        FILTER_NULL_ON_FAILURE,
                    ]
                ] : $this->options | FILTER_NULL_ON_FAILURE
            )
        };
    }
}