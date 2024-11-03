<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\CastBool;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastBoolean extends CastBool
{
}