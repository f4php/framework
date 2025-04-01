<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class DuplicateSchemaException extends Exception
{
    protected $message = 'Schema already exists';
    protected $code = 500;
}
