<?php

declare(strict_types=1);

namespace F4\DB;

use Composer\Pcre\Preg;

use InvalidArgumentException;
use F4\DB\Reference\SimpleReference;

use function is_array;
use function is_scalar;
use function mb_strtoupper;

/**
 * 
 * OrderCollection is an abstraction of sql expressions allowed inside a "ORDER BY" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class OrderCollection extends FragmentCollection
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
                } elseif (is_scalar($value) && ((mb_trim(mb_strtoupper($value)) === 'ASC') || (mb_trim(mb_strtoupper($value)) === 'DESC'))) {
                    $query = match ($quoted = (new SimpleReference($key))->delimitedIdentifier) {
                        null => sprintf('%s %s', $key, mb_trim(mb_strtoupper($value))),
                        default => sprintf('%s %s', $quoted, mb_trim(mb_strtoupper($value)))
                    };
                    $this->append(new Fragment($query));
                } else {
                    throw new InvalidArgumentException("Order epxression must be an array in the form 'field_name'=>'asc' or 'field_name'=>'desc'");
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            $this->append($expression);
        } else {
            throw new InvalidArgumentException("Order epxression must be an array in the form 'field_name'=>'asc' or 'field_name'=>'desc'");
        }
    }
}

