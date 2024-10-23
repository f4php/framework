<?php

namespace F4\Config;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class ConfigAttribute
{

    public function __construct(protected mixed $value = null)
    {

    }

    public function getValue(): mixed
    {
        return $this->value;
    }

}