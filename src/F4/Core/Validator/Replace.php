<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use Composer\Pcre\Preg;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Replace implements ValidatorAttributeInterface
{
    public function __construct(protected string $pattern, protected string $replacement, protected string $modifiers = 'u') {}
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return Preg::replace(pattern: sprintf('/%s/%s', $this->pattern, $this->modifiers), replacement: $this->replacement, subject: $value??'');
    }
}