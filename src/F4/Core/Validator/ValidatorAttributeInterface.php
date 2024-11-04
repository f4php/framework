<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
interface ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value): mixed;
}