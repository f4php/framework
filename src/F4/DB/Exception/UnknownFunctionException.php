<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use Exception;

class UnknownFunctionException extends Exception
{
    protected $message = 'Function does not exist';
    protected $code = 500;
}
