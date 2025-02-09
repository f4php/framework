<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResult;
use F4\Core\Debugger\ExportResultScalar;
use F4\Core\Debugger\ExportResultInterface;

use ReflectionFunction;
use ReflectionParameter;

use function array_map;
use function implode;

class ExportResultClosure extends ExportResultScalar implements ExportResultInterface
{
    protected bool $complex = true;
    public static function fromVariable(mixed $variable, ?string $name = null, mixed $meta = null): static
    {
        $type = 'closure';
        $preview = static::generatePreview($variable);
        $value = static::generateValue($variable, $name);
        return new self($name, $type, $preview, $value, $meta);
    }
    protected static function generatePreview(mixed $variable, ?string $name = null): string
    {
        $reflectionFunction = new ReflectionFunction($variable);
        $type = $reflectionFunction->hasReturnType() ? ': ' . $reflectionFunction->getReturnType()->__toString() : '';
        $parameters = implode(', ', array_map(function (ReflectionParameter $reflectionParameter): string {
            $type = $reflectionParameter->hasType() ? $reflectionParameter->getType()->__toString() . ' ' : '';
            $name = '$' . $reflectionParameter->name;
            $defaultValue = $reflectionParameter->isDefaultValueAvailable() ? ' = '.ExportResult::fromVariable($reflectionParameter->getDefaultValue())->getPreview() : null;
            return "{$type}{$name}{$defaultValue}";
        }, $reflectionFunction->getParameters()));
        return "function {$name}({$parameters}){$type} { /*...*/ }";
    }
    protected static function generateValue(mixed $variable, ?string $name = null): array
    {
        $reflectionFunction = new ReflectionFunction($variable);
        $parameters =  array_map(function (ReflectionParameter $reflectionParameter): array {
            $type = $reflectionParameter->hasType() ? $reflectionParameter->getType()->__toString() . ' ' : '';
            $name = '$' . $reflectionParameter->name;
            $defaultValue = $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null;
            return ExportResult::fromVariable($defaultValue, $name)->toArray();
        }, $reflectionFunction->getParameters());
        return $parameters;
    }
}
