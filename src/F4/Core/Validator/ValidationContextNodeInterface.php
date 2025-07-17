<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;

interface ValidationContextNodeInterface
{
    public function getAttribute(): ValidatorAttributeInterface;
    public function getName(): string;
    public function getType(): string;
    public function getValue(): mixed;
    public function withAttribute(ValidatorAttributeInterface $attribute): static;
    public function withName(string $name): static;
    public function withValue(mixed $value): static;
}