<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ArrayOf implements ValidatorAttributeInterface
{
    protected readonly array $definitions;
    public function __construct(mixed ...$definitions)
    {
        (function (ValidatorAttributeInterface ...$definitions): void{})(...$definitions);
        $this->definitions = $definitions;
    }
    public function getFilteredValue(mixed $value): mixed
    {
        return match (\is_array(value: $value)) {
            false => [],
            default => array_map(callback: function ($valueItem): mixed {
                    return array_reduce(array: $this->definitions, callback: fn($result, $attributeInstance): mixed => $attributeInstance->getFilteredValue($result), initial: $valueItem);
                }, array: $value)
        };
    }
}