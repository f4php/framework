<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;
use F4\DB\Parenthesize;
use F4\DB\Reference\SimpleReference;
use F4\DB\Reference\TableReferenceWithAlias;
use F4\DB\SimpleColumnReferenceCollection;

use function is_array;
use function is_numeric;

/**
 * 
 * TableWithColumnsReferenceCollection is an abstraction of sql sql expressions allowed after an "INSERT INTO" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class TableWithColumnsReferenceCollection extends FragmentCollection
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
                    if (is_array($value)) {
                        $query = match ($quoted = (new TableReferenceWithAlias($key))->delimitedIdentifier) {
                            null => $key,
                            default => $quoted
                        };
                        $this
                            ->append(new FragmentCollection($query, new Parenthesize(new SimpleColumnReferenceCollection($value))));
                    } else {
                        throw new InvalidArgumentException('Unsupported column reference');
                    }
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            throw new InvalidArgumentException('Subqueries are not supported');
        } else {
            $query = match ($quoted = (new TableReferenceWithAlias((string) $expression))->delimitedIdentifier) {
                null => (string) $expression,
                default => $quoted
            };
            $this->append(new Fragment($query, []));
        }
    }
}

