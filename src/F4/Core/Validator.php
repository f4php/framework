<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;

use F4\Core\Validator\SanitizedString;
use F4\Core\Validator\DefaultValue;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionFunction;
use ReflectionProperty;

class Validator
{
    public const int SANITIZE_STRINGS_BY_DEFAULT = 1;

    public function __construct(protected int $flags = self::SANITIZE_STRINGS_BY_DEFAULT)
    {

    }

    protected static function getFilteredValue(mixed $value, array $filters): mixed
    {
        (function (ValidatorAttributeInterface ...$filters): void {})(...$filters);
        foreach($filters as $filter) {
            $value = $filter->getFilteredValue($value);
        }
        return $value;
    }

    public function getFilteredArguments(Closure $handler, mixed $arguments): mixed
    {
        $filteredArguments = [];
        if ($parameters = (new ReflectionFunction(function: $handler))->getParameters()) {
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $type = (string)$parameter->getType(); // NB: $type could be a pipe-separated list of simple types
                $attributes = $parameter->getAttributes(name: ValidatorAttributeInterface::class, flags: ReflectionAttribute::IS_INSTANCEOF);
                $hasAttributeDefaults = count(value: $parameter->getAttributes(name: DefaultValue::class, flags: ReflectionAttribute::IS_INSTANCEOF));
                if (!isset($arguments[$name]) && !$parameter->isOptional() && !$hasAttributeDefaults) {
                    throw (new ValidationFailedException(message: "Argument '{$name}' failed validation, a value is required"))
                        ->setArgumentName(argumentName: $name)
                        ->setArgumentType(argumentType: $type); 
                }
                else {
                    $filters = [];
                    if ($attributes) {
                        $filters = [
                            ...$filters,
                            ...\array_map(
                                callback: fn($attribute): mixed => $attribute->newInstance(),
                                array: $attributes
                            )
                        ];
                    }
                    elseif(
                        ($this->flags & self::SANITIZE_STRINGS_BY_DEFAULT) && (
                        (\array_intersect(\explode(separator: '|', string: (string)$parameter->getType()), ['',  'string', '?string']))
                        || 
                        ($parameter->isDefaultValueAvailable() && \is_string(value: $parameter->getDefaultValue()))
                    )) {
                        $filters[] = new SanitizedString();
                    }
                    try {
                        $filteredArguments[$name] = self::getFilteredValue(
                            value: $arguments[$name] ?? null,
                            filters: $filters
                        ) ?? (($parameter->isDefaultValueAvailable() && !$hasAttributeDefaults) ? $parameter->getDefaultValue() : null);
                    }
                    catch (ValidationFailedException $e) {
                        $e->setArgumentName(argumentName: $name);
                        $e->setArgumentType(argumentType: $type);
                        throw $e;
                    }
                }
            }

        }
        return $filteredArguments;
    }
}