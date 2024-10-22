<?php

namespace F4\Config;

use Attribute as BaseAttribute;

#[BaseAttribute(BaseAttribute::TARGET_CLASS_CONSTANT)]
class SensitiveParameter extends Attribute
{

}