<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;

use F4\DB\Reference\ColumnReference;

use function is_array;
use function is_numeric;

/**
 * 
 * SimpleColumnReferenceCollection is an abstraction of sql expressions allowed inside, for example, a "GROUP BY" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class SimpleColumnReferenceCollection extends FragmentCollection
{
    protected const string GLUE = ', ';
    public function __construct(...$arguments)
    {
        $this->addExpression($arguments);
    }

    public function addExpression($expression): void
    {
        if (is_array($expression)) {
            foreach ($expression as $key => $value) {
                if (is_numeric($key)) {
                    $this->addExpression($value);
                } else {
                    throw new InvalidArgumentException("Complex references are not supported");
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            $this->append($expression);
        } else {
            $query = match ($quoted = (new ColumnReference((string) $expression))->delimitedIdentifier) {
                null => (string) $expression,
                default => $quoted
            };
            $this->append(new Fragment($query, []));
        }
    }
}

