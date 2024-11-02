<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\Filter;
use F4\Core\Validator\ValidatorAttributeInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class SanitizedString extends Filter implements ValidatorAttributeInterface
{
    public function __construct(protected int $flags = FILTER_SANITIZE_FULL_SPECIAL_CHARS, protected int|array $options = FILTER_FLAG_NO_ENCODE_QUOTES) {}
}