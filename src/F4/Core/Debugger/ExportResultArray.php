<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResult;
use F4\Core\Debugger\ExportResultScalar;
use F4\Core\Debugger\ExportResultInterface;

use function array_map;
use function array_keys;
use function count;
use function range;

class ExportResultArray extends ExportResultScalar implements ExportResultInterface
{
    protected bool $complex = true;
    protected static function generatePreview(mixed $variable, ?string $name = null): string
    {
        $keys = array_keys($variable);
        $compact = $keys === range(0, count($keys) - 1);
        return '[' . implode(', ', array_map(function ($key) use ($variable, $compact) {
            $value = $variable[$key];
            return match (is_numeric($key)) {
                true => match ($compact) {
                    true => ExportResult::fromVariable($value)->getPreview(),
                    default => "{$key} => " . ExportResult::fromVariable($value)->getPreview()
                },
                default => "\"{$key}\" => " . ExportResult::fromVariable($value)->getPreview()
            };
        }, array_keys($variable))) . ']';
    }
    protected static function generateValue(mixed $variable, ?string $name = null): mixed
    {
        return array_map(function ($key) use ($variable): mixed {
            return ExportResult::fromVariable($variable[$key], (string)$key)->toArray();
        }, array_keys($variable));
    }
}
