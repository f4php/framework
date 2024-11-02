<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class RegExp implements ValidatorAttributeInterface
{
    public function __construct(protected string $pattern, protected int|string $group, protected int $flags = 0) {}
    public function getFilteredValue(mixed $value, mixed $defaultValue = null): mixed
    {
        return match (\preg_match(pattern: $this->pattern, subject: $value, matches: $matches, flags: $this->flags)) {
            false, 0 => $defaultValue,
            default => $matches[$this->group] ?? $defaultValue
        };
    }
}