<?php

declare(strict_types=1);

namespace F4\DB\Exception;

class Exception extends \Exception
{
    protected $message = 'Error running database query';
    protected $code = 500;
}
