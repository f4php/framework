<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;
use F4\DB\Reference\SimpleReference;
use F4\DB\Reference\TableReferenceWithAlias;

use function is_array;
use function is_numeric;
use function is_scalar;
use function sprintf;

/**
 * 
 * TableReferenceCollection is an abstraction of sql sql expressions allowed inside a "WITH" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class WithTableReferenceCollection extends FragmentCollection
{
    protected const string GLUE = ', ';
    public function __construct(...$arguments)
    {
        $this->addExpression($arguments);
    }

    public function addExpression(mixed $expression): void
    {
        if (is_array($expression)) {
            foreach ($expression as $key => $value) {
                if (is_numeric($key)) {
                    $this->addExpression($value);
                } else {
                    if ($value instanceof FragmentInterface) {
                        $query = match ($quoted = (new SimpleReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s AS ({#::#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else if (is_scalar($value)) {
                        throw new InvalidArgumentException('Scalar values as table references are not supported');
                    } else if (is_array($value)) {
                        throw new InvalidArgumentException('Array values as table references are not supported');
                    } else {
                        throw new InvalidArgumentException('Unsupported reference');
                    }
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            throw new InvalidArgumentException('Subqueries must have an alias');
        } else {
            $query = match ($quoted = (new TableReferenceWithAlias((string) $expression))->delimitedIdentifier) {
                null => (string) $expression,
                default => $quoted
            };
            $this->append(new Fragment($query, []));
        }
    }
}

