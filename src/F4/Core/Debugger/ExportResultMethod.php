<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResultClosure;
use F4\Core\Debugger\ExportResultInterface;

use ReflectionFunction;
use ReflectionParameter;

class ExportResultMethod extends ExportResultClosure implements ExportResultInterface
{
    protected bool $complex = true;
    public static function fromVariable(mixed $variable, ?string $name = null, mixed $meta = null): static
    {
        $type = 'method';
        $preview = static::generatePreview($variable, $name);
        $value = static::generateValue($variable, $name);
        $name = preg_replace('/(^.+function\s+)|(\(\)$)/', '', $name).'()';
        return new self($name, $type, $preview, $value, $meta);
    }

}
