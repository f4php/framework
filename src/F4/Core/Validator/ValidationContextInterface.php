<?php

declare(strict_types=1);

namespace F4\Core\Validator;

interface ValidationContextInterface
{
    public function getNodes(): array;
    public function withNode(string $name, ValidatorAttributeInterface $attribute): static;
}