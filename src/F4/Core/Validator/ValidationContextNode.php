<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;

use function get_class;

class ValidationContextNode implements ValidationContextNodeInterface
{
    protected string $name;
    protected string $type;
    protected ValidatorAttributeInterface $attribute;
    public function __construct(string $name, ValidatorAttributeInterface $attribute)
    {
        $this
            ->withName($name)
            ->withAttribute($attribute);
    }
    public function getAttribute(): ValidatorAttributeInterface
    {
        return $this->attribute;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getType(): string
    {
        return $this->type;
    }
    public function withAttribute(ValidatorAttributeInterface $attribute): static
    {
        $this->attribute = $attribute;
        $this->type = get_class($attribute);
        return $this;
    }
    public function withName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
}