<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

use function mb_trim;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Trim implements ValidatorAttributeInterface
{
    public function __construct(protected readonly ?string $characters = null) {}

    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return mb_trim($value, $this->characters);
    }

}