<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function filter_var;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastBool implements ValidatorAttributeInterface
{
    public function __construct() {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return (bool) filter_var(value: $value, filter: FILTER_VALIDATE_BOOLEAN);
    }
}