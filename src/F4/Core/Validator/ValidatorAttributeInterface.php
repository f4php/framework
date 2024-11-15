<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;

interface ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value): mixed;
}