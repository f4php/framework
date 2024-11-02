<?php

declare(strict_types=1);

namespace F4\Core\Validator;

use F4\Core\Validator\IsInt;

use Attribute;

#[Attribute(Attribute::TARGET_PARAMETER)]
class IsInteger extends IsInt
{
}