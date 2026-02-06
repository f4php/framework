<?php

declare(strict_types=1);

namespace F4\Core;

use ReflectionObject;
use ReflectionClassConstant;
use F4\Config\SensitiveParameter;

use function array_keys;
use function array_reduce;
use function count;

trait SensitiveParameterAwareTrait
{
    protected function getClassConstantsWithoutSensitive(string $className, ?string $replaceWith = null): array
    {
        $object = new $className();
        $reflectionObject = new ReflectionObject($object);
        $constants = $reflectionObject->getConstants();
        return array_reduce(
            array: array_keys($constants),
            callback: function (array $result, string $constantName) use ($constants, $object, $replaceWith): array {
                $reflectionClassConstant = new ReflectionClassConstant($object, $constantName);
                $name = $reflectionClassConstant->name;
                $value = count($reflectionClassConstant->getAttributes(SensitiveParameter::class)) ? $replaceWith : $constants[$constantName];
                $result[$name] = $value;
                return $result;
            },
            initial: [],
        );
    }

}