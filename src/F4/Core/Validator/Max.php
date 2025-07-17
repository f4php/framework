<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function min;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Max implements ValidatorAttributeInterface
{
    public function __construct(protected int $max) {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return min($value, $this->max);
    }
}