<?php

namespace F4\Loader;

class Attribute {

  public readonly mixed $value;

  public function getValue(): mixed {
    return $this->value;
  }
  
}