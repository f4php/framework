<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use Exception;

class DuplicateRecordException extends Exception
{
    protected $message = 'Duplicate record error';
    protected $code = 500;
}
