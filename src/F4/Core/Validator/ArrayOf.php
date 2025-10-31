<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContext;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function array_combine;
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
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (is_array(value: $value)) {
            false => [],
            default =>
                // the use of array_combine is essential for associative arrays
                array_combine(
                    array_keys($value),
                    array_map(
                        fn(int|string $name, mixed $valueItem): mixed
                            => array_reduce(
                                array: $this->filters,
                                callback: fn($carry, $filter): mixed => 
                                    $filter->getFilteredValue($carry, new ValidationContext($context)->withNode((string)$name, $filter, $carry)),
                                initial: $valueItem
                            ),
                        array_keys($value),
                        array_values($value)
                    ),
                )
        };
    }
}