<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;
use F4\Core\Validator\ValidationContextInterface;
use F4\Core\Validator\ValidationContextNode;

use function array_reduce;

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
    public function getPathAsString(): string
    {
        return array_reduce(
            array: $this->nodes,
            callback: fn($result, $node) => $result ? "{$result}[{$node->getName()}]" : $node->getName(),
            initial: '',
        );
    }
    public function withNode(string $name, ?ValidatorAttributeInterface $attribute = null, mixed $value = null): static
    {
        $this->nodes[] = new ValidationContextNode(name: $name, attribute: $attribute, value: $value);
        return $this;
    }
}