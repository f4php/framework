<?php

declare(strict_types=1);

namespace F4\Core\Exception;

class Exception extends \Exception
{
    protected $code = 500;
    protected $message = 'Internal Server Error';
}
