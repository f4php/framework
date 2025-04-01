<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class DuplicateTableException extends Exception
{
    protected $message = 'Table already exists';
    protected $code = 500;
}
