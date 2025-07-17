<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationContextNode;

class ValidationContext implements ValidationContextInterface
{
    protected array $nodes = [];
    public function __construct(?ValidationContextInterface $parentContext = null)
    {
        if ($parentContext) {
            $this->nodes = $parentContext->getNodes();
        }
    }
    public function getNodes(): array
    {
        return $this->nodes;
    }
    public function withNode(string $name, ValidatorAttributeInterface $attribute): static
    {
        $this->nodes[] = new ValidationContextNode($name, $attribute);
        return $this;
    }
}