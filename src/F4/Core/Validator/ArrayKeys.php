<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use InvalidArgumentException;
use F4\Core\Validator\ValidationContext;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function is_array;
use function is_string;

#[Attribute(Attribute::TARGET_PARAMETER)]
class ArrayKeys implements ValidatorAttributeInterface
{
    protected readonly array $definitions;
    public function __construct(array $definitions)
    {
        if (array_filter(array: array_keys($definitions), callback: fn($key): bool => !is_string(value: $key))) {
            throw new InvalidArgumentException(message: "Field name must be a string");
        }
        (new class {
            function __invoke(array|ValidatorAttributeInterface $definitions): void
            {
                is_array(value: $definitions) && array_map(callback: function ($definition): void {
                    $this($definition); 
                }, array: $definitions);
            }
        })($definitions);
        $this->definitions = $definitions;
    }
    protected function getDefinitions()
    {
        return $this->definitions;
    }
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        $result = [];
        foreach ($this->definitions as $name => $filter) {
            $result[$name] = match (is_array(value: $filter)) {
                true => array_reduce(
                    array: (array) $filter,
                    callback: fn(mixed $carry, ValidatorAttributeInterface $filter): mixed 
                        => $filter->getFilteredValue($carry, new ValidationContext($context)->withNode($name, $filter, $carry)),
                    initial: $value[$name] ?? null
                ),
                default => $filter->getFilteredValue($value[$name] ?? null, new ValidationContext($context)->withNode($name, $filter, $value[$name] ?? null))
            };
        }
        return $result;
    }
}