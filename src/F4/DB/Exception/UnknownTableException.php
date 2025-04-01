<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class UnknownTableException extends Exception
{
    protected $message = 'Table does not exist';
    protected $code = 500;
}
