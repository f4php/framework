<?php

declare(strict_types=1);

namespace F4\Core\Validator;

interface ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value): mixed;
}