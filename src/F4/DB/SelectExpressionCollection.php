<?php

declare(strict_types=1);

namespace F4\DB;

use Composer\Pcre\Preg;

use F4\DB\Reference\SimpleReference;
use F4\DB\Reference\ColumnReferenceWithAlias;
use InvalidArgumentException;

use function is_array;
use function is_numeric;
use function is_scalar;
use function sprintf;

/**
 * 
 * SelectExpressionCollection is an abstraction of sql expressions allowed inside a "SELECT" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class SelectExpressionCollection extends FragmentCollection
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
                        $query = match ($quoted = (new SimpleReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('({#,...#}) AS %s', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else if ($value instanceof FragmentInterface) {
                        $query = match ($quoted = (new SimpleReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('({#::#}) AS %s', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else if ($value === null || is_scalar($value)) {
                        $query = match ($quoted = (new SimpleReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('{#} AS %s', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else {
                        throw new InvalidArgumentException('Unsupported type');
                    }
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            $this->append($expression);
        } else {
            $query = match ($quoted = (new ColumnReferenceWithAlias((string) $expression))->delimitedIdentifier) {
                null => (string) $expression,
                default => $quoted
            };
            $this->append(new Fragment($query, []));
        }
    }
}

