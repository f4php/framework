<?php

namespace F4\Loader;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class ConfigAttribute {

  public readonly mixed $value;

  public function getValue(): mixed {
    return $this->value;
  }
  
}