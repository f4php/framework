<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class DuplicateFunctionException extends Exception
{
    protected $message = 'Function already exists';
    protected $code = 500;
}
