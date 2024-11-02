<?php

declare(strict_types=1);

namespace F4\Core;

use Closure;
use ReflectionFunction;
use ReflectionAttribute;

use InvalidArgumentException;

use F4\Core\Validator\SanitizedString;
use F4\Core\Validator\ValidationFailedException;
use F4\Core\Validator\ValidatorAttributeInterface;

class Validator
{
    public const SANITIZE_STRINGS_BY_DEFAULT = 0x01;

    public function __construct(protected int $flags = self::SANITIZE_STRINGS_BY_DEFAULT)
    {

    }

    protected static function getFilteredValue(mixed $value, array $filters, mixed $default = null): mixed
    {
        (function (ValidatorAttributeInterface ...$filters): void {})(...$filters);
        foreach($filters as $filter) {
            $value = $filter->getFilteredValue($value, $default);
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
                if (!isset($arguments[$name]) && !$parameter->isOptional()) {
                    throw (new ValidationFailedException(message: "Argument '{$name}' failed validation, a value is required"))
                        ->setArgumentName(argumentName: $name)
                        ->setArgumentType(argumentType: $type); 
                } elseif (empty($arguments[$name]) && $parameter->isDefaultValueAvailable()) {
                    // There's nothing to filter if there's no user input,
                    // so we trust the default parameter value to always be valid and never filter it
                    $filteredArguments[$name] = $parameter->getDefaultValue();
                } else {
                    $filters = [];
                    if ($attributes = $parameter->getAttributes(name: ValidatorAttributeInterface::class, flags: ReflectionAttribute::IS_INSTANCEOF)) {
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
                            value: $arguments[$name],
                            filters: $filters,
                            default: $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null
                        );
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