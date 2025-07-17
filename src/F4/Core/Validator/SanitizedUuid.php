<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use Composer\Pcre\Preg;
use InvalidArgumentException;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class SanitizedUuid implements ValidatorAttributeInterface
{
    protected const array PATTERNS = [
        0 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
        1 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-1[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
        2 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-2[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
        3 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-3[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
        4 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
        5 => '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-5[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
    ];
    public function __construct(protected int $version = 0) {
        if(!isset(self::PATTERNS[$version])) {
            throw new InvalidArgumentException(message: "UUID version {$version} is not supported");
        }
    }
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return match (Preg::isMatch(pattern: self::PATTERNS[$this->version], subject: $value ?? '')) {
            false => null,
            default => $value
        };
    }
}
