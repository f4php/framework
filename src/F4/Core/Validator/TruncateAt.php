<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use InvalidArgumentException;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function mb_substr;

#[Attribute(Attribute::TARGET_PARAMETER)]
class TruncateAt implements ValidatorAttributeInterface
{
    public function __construct(protected readonly int $length) {
        if($length < 0) {
            throw new InvalidArgumentException('Length must be non-negative');
        }
    }
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return mb_substr(
            string: $value,
            start: 0,
            length: $this->length,
        );
    }

}