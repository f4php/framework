<?php

namespace F4\Config;

use Attribute;
use F4\Config\ConfigAttribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
class SensitiveParameter extends ConfigAttribute
{

}