<?php

namespace F4\Config;

use Attribute as BaseAttribute;

#[BaseAttribute(BaseAttribute::TARGET_CLASS_CONSTANT)]
class EnvironmentVariable extends Attribute
{

  public function __construct(?string $environmentVariableName=null)
  {
    $this->value = $_SERVER[$environmentVariableName] ?? $_ENV[$environmentVariableName] ?? null;
  }

}