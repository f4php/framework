<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use Exception;

class DuplicateColumnException extends Exception
{
    protected $message = 'Column already exists';
    protected $code = 500;
}
