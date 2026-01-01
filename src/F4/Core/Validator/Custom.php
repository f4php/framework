<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidatorAttributeInterface;
use InvalidArgumentException;

use function is_callable;

#[Attribute(Attribute::TARGET_PARAMETER)]
class Custom implements ValidatorAttributeInterface
{
    protected readonly mixed $handler;
    public function __construct(mixed $handler) {
        $this->handler = is_callable($handler) ? $handler : throw new InvalidArgumentException('Only callable arguments are supported');
    }
    public function getFilteredValue(mixed $value, ValidationContextInterface $context): mixed
    {
        return ($this->handler)($value, $context);
    }
}