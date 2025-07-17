<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\ValidatorAttributeInterface;

use function get_class;

class ValidationContextNode implements ValidationContextNodeInterface
{
    protected string $name;
    protected string $type;
    protected mixed $value;
    protected ?ValidatorAttributeInterface $attribute = null;
    public function __construct(string $name, ?ValidatorAttributeInterface $attribute = null, mixed $value = null)
    {
        $this->withName($name);
        $this->withValue($value);
        if($attribute) {
            $this->withAttribute($attribute);
        }
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
    public function getValue(): mixed
    {
        return $this->value;
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
    public function withValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }
}