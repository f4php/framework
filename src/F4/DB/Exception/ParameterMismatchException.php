<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class ParameterMismatchException extends Exception
{
    protected $message = 'Parameter mismatch';
    protected $code = 500;
}
