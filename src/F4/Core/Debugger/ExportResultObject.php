<?php

declare(strict_types=1);

namespace F4\Core\Debugger;

use F4\Core\Debugger\ExportResult;
use F4\Core\Debugger\ExportResultScalar;
use F4\Core\Debugger\ExportResultInterface;
use F4\Config\SensitiveParameter;

use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;

use function array_map;
use function implode;

class ExportResultObject extends ExportResultScalar implements ExportResultInterface
{
    protected bool $complex = true;
    protected static function generateObjectConstanstsPreview(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        $constants = $reflectionObject->getConstants($filter);
        return array_map(function ($constantName) use ($object): string {
            $reflectionClassConstant = new ReflectionClassConstant($object, $constantName);
            $final = $reflectionClassConstant->isFinal() ? 'final ' : '';
            $modifier = $reflectionClassConstant->isPublic() ? 'public ' : ($reflectionClassConstant->isProtected() ? 'protected ' : ($reflectionClassConstant->isPrivate() ? 'private ' : ''));
            $type = $reflectionClassConstant->hasType() ? $reflectionClassConstant->getType()->__toString() . ' ' : '';
            $name = $reflectionClassConstant->name;
            return "{$final}{$modifier}{$type}{$name};";
        }, array_keys($constants));
    }
    protected static function generateObjectConstanstsValue(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        $constants = $reflectionObject->getConstants($filter);
        return array_map(function ($constantName) use ($constants, $object): array {
            $reflectionClassConstant = new ReflectionClassConstant($object, $constantName);
            $final = $reflectionClassConstant->isFinal() ? 'final ' : '';
            $modifier = $reflectionClassConstant->isPublic() ? 'public ' : ($reflectionClassConstant->isProtected() ? 'protected ' : ($reflectionClassConstant->isPrivate() ? 'private ' : ''));
            $type = $reflectionClassConstant->hasType() ? $reflectionClassConstant->getType()->__toString() . ' ' : '';
            $name = $reflectionClassConstant->name;
            // Strip sensitive parameters
            $value = count($reflectionClassConstant->getAttributes(SensitiveParameter::class)) ? null : $constants[$constantName];
            return ExportResult::fromVariable($value, $name, ['modifier'=>"{$final}{$modifier}", 'type'=>$type])->toArray();
        }, array_keys($constants));
    }
    protected static function generateObjectPropertiesPreview(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        return array_map(function (ReflectionProperty $reflectionProperty): string {
            $modifier = $reflectionProperty->isPublic() ? 'public ' : ($reflectionProperty->isProtected() ? 'protected ' : ($reflectionProperty->isPrivate() ? 'private ' : ''));
            $static = $reflectionProperty->isStatic() ? 'static ' : '';
            $type = $reflectionProperty->hasType() ? $reflectionProperty->getType()->__toString() . ' ' : '';
            $name = '$' . $reflectionProperty->name;
            return "{$modifier}{$static}{$type}{$name};";
        }, $reflectionObject->getProperties($filter));
    }
    protected static function generateObjectPropertiesValue(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        return array_map(function (ReflectionProperty $reflectionProperty) use ($object): array {
            $modifier = $reflectionProperty->isPublic() ? 'public ' : ($reflectionProperty->isProtected() ? 'protected ' : ($reflectionProperty->isPrivate() ? 'private ' : ''));
            $static = $reflectionProperty->isStatic() ? 'static ' : '';
            $type = $reflectionProperty->hasType() ? $reflectionProperty->getType()->__toString() . ' ' : '';
            $name = '$' . $reflectionProperty->name;
            $value = $reflectionProperty->isInitialized($object) ? $reflectionProperty->getValue($object) : ( $reflectionProperty->hasDefaultValue() ? $reflectionProperty->getDefaultValue() : null);
            return ExportResult::fromVariable($value, $name, ['modifier'=>"{$modifier}{$static}", 'type'=>$type])->toArray();
        }, $reflectionObject->getProperties($filter));
    }
    protected static function generateObjectMethodsPreview(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        return array_map(function (ReflectionMethod $reflectionMethod): string {
            $modifier = $reflectionMethod->isPublic() ? 'public ' : ($reflectionMethod->isProtected() ? 'protected ' : ($reflectionMethod->isPrivate() ? 'private ' : ''));
            $static = $reflectionMethod->isStatic() ? 'static ' : '';
            $type = $reflectionMethod->hasReturnType() ? ': ' . $reflectionMethod->getReturnType()->__toString() : '';
            $name = $reflectionMethod->name;
            $parameters = implode(', ', array_map(function (ReflectionParameter $reflectionParameter): string {
                $type = $reflectionParameter->hasType() ? $reflectionParameter->getType()->__toString() . ' ' : '';
                $name = '$' . $reflectionParameter->name;
                return "{$type}{$name}";
            }, $reflectionMethod->getParameters()));
            return "{$modifier}{$static}function {$name}({$parameters}){$type} {/*...*/}";
        }, $reflectionObject->getMethods($filter));
    }
    protected static function generateObjectMethodsValue(object $object, ?int $filter = null): array
    {
        $reflectionObject = new ReflectionObject($object);
        return array_map(function (ReflectionMethod $reflectionMethod) use ($object): array {
            $modifier = $reflectionMethod->isPublic() ? 'public ' : ($reflectionMethod->isProtected() ? 'protected ' : ($reflectionMethod->isPrivate() ? 'private ' : ''));
            $static = $reflectionMethod->isStatic() ? 'static ' : '';
            $name = $reflectionMethod->name;
            return ExportResultMethod::fromVariable($reflectionMethod->getClosure($object), "{$name}", ['modifier' => "{$modifier}{$static}", 'type'=>'function'])->toArray();
        }, $reflectionObject->getMethods($filter));
    }
    protected static function generatePreview(mixed $variable, ?string $name = null): string
    {
        $members = implode(' ', [
            ...self::generateObjectConstanstsPreview($variable, ReflectionClassConstant::IS_PUBLIC),
            ...self::generateObjectPropertiesPreview($variable, ReflectionProperty::IS_PUBLIC),
            ...self::generateObjectConstanstsPreview($variable, ReflectionClassConstant::IS_PROTECTED),
            ...self::generateObjectPropertiesPreview($variable, ReflectionProperty::IS_PROTECTED),
            ...self::generateObjectConstanstsPreview($variable, ReflectionClassConstant::IS_PRIVATE),
            ...self::generateObjectPropertiesPreview($variable, ReflectionProperty::IS_PRIVATE),
            ...self::generateObjectMethodsPreview($variable, ReflectionMethod::IS_PUBLIC),
            ...self::generateObjectMethodsPreview($variable, ReflectionMethod::IS_PROTECTED),
            ...self::generateObjectMethodsPreview($variable, ReflectionMethod::IS_PRIVATE),
        ]);
        $reflectionObject = new ReflectionObject($variable);
        $className = $reflectionObject->name;
        return "class {$className} { {$members} }";
    }
    protected static function generateValue(mixed $variable, ?string $name = null): mixed
    {
        return [
            ...self::generateObjectConstanstsValue($variable, ReflectionClassConstant::IS_PUBLIC),
            ...self::generateObjectConstanstsValue($variable, ReflectionClassConstant::IS_PROTECTED),
            ...self::generateObjectConstanstsValue($variable, ReflectionClassConstant::IS_PRIVATE),
            ...self::generateObjectPropertiesValue($variable, ReflectionClassConstant::IS_PUBLIC),
            ...self::generateObjectPropertiesValue($variable, ReflectionClassConstant::IS_PROTECTED),
            ...self::generateObjectPropertiesValue($variable, ReflectionClassConstant::IS_PRIVATE),
            ...self::generateObjectMethodsValue($variable, ReflectionMethod::IS_PUBLIC),
            ...self::generateObjectMethodsValue($variable, ReflectionMethod::IS_PROTECTED),
            ...self::generateObjectMethodsValue($variable, ReflectionMethod::IS_PRIVATE),
        ];
    }
}
