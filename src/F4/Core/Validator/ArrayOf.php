<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

use function array_map;
use function array_reduce;
use function is_array;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ArrayOf implements ValidatorAttributeInterface
{
    protected readonly array $filters;
    public function __construct(mixed ...$filters)
    {
        (function (ValidatorAttributeInterface ...$filters): void{})(...$filters);
        $this->filters = $filters;
    }
    public function getFilteredValue(mixed $value): mixed
    {
        return match (is_array(value: $value)) {
            false => [],
            default => array_map(callback: function ($valueItem): mixed {
                    return array_reduce(array: $this->filters, callback: fn($result, $attributeInstance): mixed => $attributeInstance->getFilteredValue($result), initial: $valueItem);
                }, array: $value)
        };
    }
}