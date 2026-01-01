<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function preg_match;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsRegExpMatch implements ValidatorAttributeInterface
{
    public function __construct(protected readonly string $pattern, protected readonly int $flags = 0) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (preg_match(pattern: $this->pattern, subject: $value, matches: $matches, flags: $this->flags)) {
            false, 0 => throw new ValidationFailedException(message: "{$value} did not match regular expression")
                ->withContext($context),
            default => $value
        };
    }
}