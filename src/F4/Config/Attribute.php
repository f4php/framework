<?php

namespace F4\Config;

use Attribute as BaseAttribute;

#[BaseAttribute(BaseAttribute::TARGET_CLASS_CONSTANT)]
class Attribute 
{

  public function __construct(protected mixed $value=null)
  {

  }

  public function getValue(): mixed 
  {
    return $this->value;
  }
  
}