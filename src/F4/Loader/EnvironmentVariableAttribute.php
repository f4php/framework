<?php

namespace F4\Loader;

class EnvironmentVariableAttribute extends Attribute {

  public function __construct(?string $enviornmentVariableName=null) {
    $this->value = $_SERVER[$enviornmentVariableName] ?? $_ENV[$enviornmentVariableName] ?? null;
  }

}