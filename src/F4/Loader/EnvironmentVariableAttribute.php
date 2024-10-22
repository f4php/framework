<?php

namespace F4\Loader;

use Attribute;

class EnvironmentVariableAttribute {

  public readonly string $value;

  public function __construct(?string $enviornmentVariableName=null) {
    $this->value = $_SERVER[$enviornmentVariableName] ?? $_ENV[$enviornmentVariableName] ?? null;
  }
}