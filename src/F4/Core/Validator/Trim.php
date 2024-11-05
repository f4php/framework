<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Trim implements ValidatorAttributeInterface
{
    public function __construct(protected ?string $characters = null)
    {
    }

    public function getFilteredValue(mixed $value): mixed
    {
        return \mb_trim($value, $this->characters);
    }

}