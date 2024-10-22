<?php

namespace F4\Loader;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class EnvironmentVariable extends ConfigAttribute {

  public function __construct(?string $environmentVariableName=null) {
    $this->value = $_SERVER[$environmentVariableName] ?? $_ENV[$environmentVariableName] ?? null;
  }

}