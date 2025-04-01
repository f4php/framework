<?php

declare(strict_types=1);

namespace F4\DB\Exception;

use F4\DB\Exception\Exception;

class InvalidTableDefinitionException extends Exception
{
    protected $message = 'Table definition error';
    protected $code = 500;
}
