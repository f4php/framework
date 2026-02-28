<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;

use F4\Core\Validator\SanitizedString;
use F4\Core\Validator\DefaultValue;
use F4\Core\Validator\ValidationContext;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

use ReflectionAttribute;
use ReflectionFunction;

use function array_intersect;
use function array_map;
use function array_reduce;
use function class_exists;
use function count;
use function explode;
use function in_array;
use function is_string;

class Validator
{
    public const int SANITIZE_STRINGS_BY_DEFAULT = 1;
    public const int ALL_ATTRIBUTES_MUST_BE_CLASSES = 1 << 1;
    public function __construct(protected int $flags = self::SANITIZE_STRINGS_BY_DEFAULT) {}
    protected function findInvalidAttribute(array $attributes): ?ReflectionAttribute
    {
        foreach ($attributes as $attribute) {
            if (!class_exists(class: $attribute->getName())) {
                return $attribute;
            }
        }
        return null;
    }
    public function getFilteredArguments(Closure $handler, mixed $arguments): mixed
    {
        $filteredArguments = [];
        if ($parameters = new ReflectionFunction(function: $handler)->getParameters()) {
            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                if ($parameter->isVariadic()) {
                    throw new ValidationFailedException(message: "Variadic parameters are not supported, '{$name}' is variadic")
                        ->withArgumentName(argumentName: $name)
                        ;
                }
                $type = (string) $parameter->getType(); // NB: $type could be a pipe-separated list of simple types
                $attributes = $parameter->getAttributes(name: ValidatorAttributeInterface::class, flags: ReflectionAttribute::IS_INSTANCEOF);
                $hasAttributeDefaults = count(value: $parameter->getAttributes(name: DefaultValue::class, flags: ReflectionAttribute::IS_INSTANCEOF)) > 0;
                $defaultValue = ($parameter->isDefaultValueAvailable() && !$hasAttributeDefaults) ? $parameter->getDefaultValue() : null;
                $value = ($arguments[$name]??null) ?? $defaultValue;
                if (
                    ($this->flags & self::ALL_ATTRIBUTES_MUST_BE_CLASSES) && (
                        null !== ($invalidAttribute = $this->findInvalidAttribute(attributes: $parameter->getAttributes())))
                ) {
                    throw new ValidationFailedException(message: "All attributes must be valid class names, '{$invalidAttribute->getName()}' is not")
                        ->withArgumentName(argumentName: $name)
                        ;
                }
                if (!isset($arguments[$name]) && !$parameter->isOptional() && !$hasAttributeDefaults) {
                    throw (new ValidationFailedException(message: "Argument '{$name}' failed validation, a value is required"))
                        ->withArgumentName(argumentName: $name)
                        ->withArgumentType(argumentType: $type)
                        ->withArgumentValue(argumentValue: $arguments[$name] ?? null)
                        ;
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
                        $value = array_reduce(
                            array: $filters,
                            callback: fn(mixed $value, ValidatorAttributeInterface $filter): mixed
                                => $filter->getFilteredValue($value, new ValidationContext()->withNode($name, $filter, $value)),
                            initial: $value
                        ) ?? $defaultValue;
                        $filteredArguments[$name] = match($type) {
                            'int' => (int) $value,
                            'bool' => (bool) (!in_array(needle: $value, haystack: [false, 0, '0', 'false', ''], strict: true)),
                            'float' => (float) $value,
                            default => $value,
                        };
                    } catch (ValidationFailedException $e) {
                        throw $e
                            ->withArgumentName(argumentName: $name)
                            ->withArgumentType(argumentType: $type)
                            ->withArgumentValue(argumentValue: $value);
                    }
                }
            }

        }
        return $filteredArguments;
    }
}