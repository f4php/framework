<?php

declare(strict_types=1);

namespace F4\DB;

use F4\DB\Reference\ColumnReference;

use function is_array;
use function is_numeric;
use function is_scalar;

/**
 * 
 * ConditionCollection is an abstraction of sql expressions allowed inside a "WHERE" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class ConditionCollection extends FragmentCollection
{
    protected const string GLUE = ' AND ';
    public function __construct(...$arguments) {
        $this->addExpression($arguments);
    }
    static public function of(...$arguments): ConditionCollection {
        $instance = new static(...$arguments);
        return $instance;
    }
    protected function addExpression($expression) {
        if(is_array($expression)) {
            foreach($expression as $key=>$value) {
                if(is_numeric($key)) {
                    $this->addExpression($value);
                }
                else {
                    if(is_scalar($value)) {
                        $query = match($quoted = new ColumnReference($key)->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s = {#}', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    }
                    elseif(is_array($value)) {
                        $query = match($quoted = new ColumnReference($key)->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s IN ({#,...#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    }
                    elseif($value instanceof FragmentInterface) {
                        $query = match($quoted = new ColumnReference($key)->delimitedIdentifier) {
                            null => $key,
                            /**
                             * By default, we assume that subquery returns a single value
                             * If not, a "field" IN ({#::}) is still supported in custom query mode
                             */
                            default => sprintf('%s = ({#::#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    }
                }
            }
        }
        elseif($expression instanceof FragmentInterface) {
            $this->append($expression);
        }
        else {
            $this->append(new Fragment($expression, []));
        }
    }
}

