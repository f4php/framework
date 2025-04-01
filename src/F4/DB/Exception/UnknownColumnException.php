<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class UnknownColumnException extends Exception
{
    protected $message = 'Column does not exist';
    protected $code = 500;
}
