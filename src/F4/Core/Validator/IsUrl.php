<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function filter_var;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsUrl implements ValidatorAttributeInterface
{
    public function __construct(protected array|int $options = 0) {}
    public function getFilteredValue(mixed $value): mixed
    {
        return match (filter_var(value: $value, filter: FILTER_VALIDATE_URL, options: $this->options)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not a valid URL"),
            default => $value
        };
    }
}