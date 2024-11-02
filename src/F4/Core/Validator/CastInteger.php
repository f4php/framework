<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\CastInt;
use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class CastInteger extends CastInt
{
}