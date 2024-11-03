<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use Attribute;
use F4\Core\Validator\CastInt;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastInteger extends CastInt
{
}