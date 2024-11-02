<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\CastBool;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastBoolean extends CastBool
{
}