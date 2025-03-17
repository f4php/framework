<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;

use function is_array;
use function is_numeric;
use function is_scalar;

/**
 * 
 * SelectExpressionCollection is an abstraction of sql expressions allowed inside an "INSERT ... VALUES (...)" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class ValueExpressionCollection extends FragmentCollection
{
    protected const string GLUE = ', ';
    public function __construct(...$arguments) {
        $this->addExpression($arguments);
    }

    public function addExpression(mixed $expression): void {
        if(is_array($expression)) {
            foreach($expression as $key=>$value) {
                if(is_numeric($key)) {
                    $this->addExpression($value);
                }
                else {
                    if($value === null || is_scalar($value)) {
                        $this->append(new Fragment($key, [$value]));
                    }
                    else if(is_array($value)) {
                        throw new InvalidArgumentException("Complex references are not supported");
                    }
                    else if($value instanceof FragmentInterface) {
                        throw new InvalidArgumentException("Complex references are not supported");
                    }
                    else {
                        throw new InvalidArgumentException("Unsupported expression");
                    }
                }
            }
        }
        elseif($expression instanceof FragmentInterface) {
            $this->append($expression);
        }
        else {
            $this->append(new Fragment('{#}', [$expression]));
        }
    }
}

