<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

use function in_array;

#[Attribute(Attribute::TARGET_PARAMETER)]
class OneOf implements ValidatorAttributeInterface
{
    public function __construct(protected array $values) {}
    public function getFilteredValue(mixed $value): mixed
    {
        return match (in_array(needle: $value, haystack: $this->values)) {
            true => $value,
            default => null
        };
    }
}