<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class SyntaxErrorException extends Exception
{
    protected $message = 'Syntax error';
    protected $code = 500;
}
