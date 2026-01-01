<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function filter_var;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsEmail implements ValidatorAttributeInterface
{
    public function __construct(protected readonly array|int $options = 0) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (filter_var(value: $value, filter: FILTER_VALIDATE_EMAIL, options: $this->options)) {
            false => throw new ValidationFailedException(message: "'{$value}' is not a valid email address")
                ->withContext($context),
            default => $value
        };
    }
}