<?php

declare(strict_types=1);

namespace F4\Config;

use Attribute;
use F4\Config\ConfigAttribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
class SensitiveParameterKey extends ConfigAttribute
{
    public function __construct(protected mixed $value)
    {
    }
}