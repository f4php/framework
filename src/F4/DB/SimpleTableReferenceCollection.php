<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;

use F4\DB\Reference\TableReference;

use function is_array;
use function is_numeric;

/**
 * 
 * SimpleTableReferenceCollection is an abstraction of sql expressions allowed inside, for example, a "UPDATE" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class SimpleTableReferenceCollection extends FragmentCollection
{
    protected const string GLUE = ', ';
    public function __construct(...$arguments) {
        $this->addExpression($arguments);
    }

    protected function addExpression(mixed $expression): void {
        if(is_array($expression)) {
            foreach($expression as $key=>$value) {
                if(is_numeric($key)) {
                    $this->addExpression($value);
                }
                else {
                    throw new InvalidArgumentException("Complex arguments to groupBy are not supported");
                }
            }
        }
        elseif($expression instanceof FragmentInterface) {
            $this->append($expression);
        }
        else {
            $query = match($quoted = (new TableReference((string)$expression))->delimitedIdentifier) {
                null => (string)$expression,
                default => $quoted
            };
            $this->append(new Fragment($query, []));
        }
    }
}

