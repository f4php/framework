<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;

use F4\Core\Validator\SanitizedString;
use F4\Core\Validator\DefaultValue;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use ReflectionAttribute;
use ReflectionFunction;

use function array_intersect;
use function array_map;
use function class_exists;
use function count;
use function explode;
use function is_string;

class Validator
{
    public const int SANITIZE_STRINGS_BY_DEFAULT = 1;
    public const int ALL_ATTRIBUTES_MUST_BE_CLASSES = 1 << 1;
    public function __construct(protected int $flags = self::SANITIZE_STRINGS_BY_DEFAULT) {}
    protected static function getFilteredValue(mixed $value, array $filters): mixed
    {
        (function (ValidatorAttributeInterface ...$filters): void{})(...$filters);
        foreach ($filters as $filter) {
            $value = $filter->getFilteredValue($value);
        }
        return $value;
    }
    protected function findInvalidAttributeName(array $attributes): ?string
    {
        foreach ($attributes as $attribute) {
            if (!class_exists(class: $name = $attribute->getName())) {
                return $name;
            }
        }
        return null;
    }
    public function getFilteredArguments(Closure $handler, mixed $arguments): mixed
    {
        $filteredArguments = [];
        if ($parameters = (new ReflectionFunction(function: $handler))->getParameters()) {
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                if ($parameter->isVariadic()) {
                    throw new ValidationFailedException(message: "Variadic parameters are not supported, '{$name}' is variadic");
                }
                $type = (string) $parameter->getType(); // NB: $type could be a pipe-separated list of simple types
                $attributes = $parameter->getAttributes(name: ValidatorAttributeInterface::class, flags: ReflectionAttribute::IS_INSTANCEOF);
                if (
                    ($this->flags & self::ALL_ATTRIBUTES_MUST_BE_CLASSES) && (
                        null !== ($invalidAttributeName = $this->findInvalidAttributeName(attributes: $parameter->getAttributes())))
                ) {
                    throw new ValidationFailedException(message: "All argument must be valid class names, '{$invalidAttributeName}' is not");
                }
                $hasAttributeDefaults = count(value: $parameter->getAttributes(name: DefaultValue::class, flags: ReflectionAttribute::IS_INSTANCEOF)) > 0;
                if (!isset($arguments[$name]) && !$parameter->isOptional() && !$hasAttributeDefaults) {
                    throw (new ValidationFailedException(message: "Argument '{$name}' failed validation, a value is required"))
                        ->setArgumentName(argumentName: $name)
                        ->setArgumentType(argumentType: $type);
                } else {
                    $filters = [];
                    if ($attributes) {
                        $filters = [
                            ...$filters,
                            ...array_map(
                                callback: fn($attribute): mixed => $attribute->newInstance(),
                                array: $attributes,
                            )
                        ];
                    } elseif (
                        ($this->flags & self::SANITIZE_STRINGS_BY_DEFAULT) && (
                            (array_intersect(explode(separator: '|', string: $type), ['', 'string', '?string']))
                            ||
                            ($parameter->isDefaultValueAvailable() && is_string(value: $parameter->getDefaultValue()))
                        )
                    ) {
                        $filters[] = new SanitizedString();
                    }
                    try {
                        $defaultValue = ($parameter->isDefaultValueAvailable() && !$hasAttributeDefaults) ? $parameter->getDefaultValue() : null;
                        $filteredArguments[$name] = self::getFilteredValue(
                            value: ($arguments[$name]??null) ?? $defaultValue,
                            filters: $filters,
                        ) ?? $defaultValue;
                    } catch (ValidationFailedException $e) {
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