<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
trait WithDefaultTrait {

    protected mixed $defaultValue = null;
    public function getDefaultValue(): mixed {
        return $this->defaultValue;
    }
    public function withDefaultValue(mixed $defaultValue): static {
        return $this->withDefault($defaultValue);
    }
    public function withDefault(mixed $defaultValue): static {
        $this->defaultValue = $defaultValue;
        return $this;
    }

}