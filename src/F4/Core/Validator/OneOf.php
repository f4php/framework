<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class OneOf implements ValidatorAttributeInterface
{
    public function __construct(protected array $values, mixed $defaultValue = null) {
        if($defaultValue !== null) {
            $this->defaultValue = $defaultValue;
        }
    }
    public function getFilteredValue(mixed $value): mixed
    {
        return match (\in_array(needle: $value, haystack: $this->values)) {
            true => $value,
            default => null
        };
    }
}