<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResultScalar;
use F4\Core\Debugger\ExportResultInterface;

use function get_resource_type;
use function get_resource_id;

class ExportResultResource extends ExportResultScalar implements ExportResultInterface
{
    protected bool $complex = false;
    protected static function generatePreview(mixed $variable, ?string $name = null): string
    {
        return sprintf('resource("%s:%d")', get_resource_type($variable), get_resource_id($variable));
    }
    protected static function generateValue(mixed $variable, ?string $name = null): mixed
    {
        return self::generatePreview($variable);
    }
}
