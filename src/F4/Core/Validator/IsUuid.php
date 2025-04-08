<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use function preg_match;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsUuid implements ValidatorAttributeInterface
{
    protected const array PATTERNS = [
        4 => '~^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$~'
    ];

    public function __construct(protected int $version = 4) {}
    public function getFilteredValue(mixed $value): mixed
    {
        return match (preg_match(pattern: self::PATTERNS[$this->version], subject: $value)) {
            false, 0 => throw new ValidationFailedException(message: "{$value} is not a valid UUID"),
            default => $value
        };
    }
}


