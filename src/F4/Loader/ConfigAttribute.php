<?php

namespace F4\Loader;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class ConfigAttribute {

  public readonly mixed $value;

  public function getValue(): mixed {
    return $this->value;
  }
  
}