<?php

namespace F4\Config;

use Attribute;
use Cekurte\Environment\Environment;
use F4\Config\ConfigAttribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class FromEnvironmentVariable extends ConfigAttribute
{
    /**
     * 
     * Uses parser from Cekurte\Environment, providing support for extended syntax like arrays and json data
     * 
     * @param mixed $environmentVariableName
     */
    public function __construct(string $name)
    {
        $this->value = Environment::get(name: $name);
    }

}