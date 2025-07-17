<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidationContextInterface;

interface ValidatorAttributeInterface
{
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed;
}