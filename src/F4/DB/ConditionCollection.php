<?php

declare(strict_types=1);

namespace F4\DB;

use Composer\Pcre\Preg;
use F4\DB\Reference\ColumnReference;
use InvalidArgumentException;

use function is_array;
use function is_numeric;
use function is_scalar;
use function sprintf;

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
    public function __construct(...$arguments)
    {
        $this->addExpression($arguments);
    }
    public function getQuery(): string
    {
        return match(empty($query = implode(static::GLUE, array_filter(array_map(function (FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments))))) {
            true => '',
            default => match($this->prefix) {
                null => sprintf('(%s)', Preg::replace('/^\((.*)\)$/', '$1', $query)),
                default => sprintf('%s %s', $this->prefix, $query)
            }
        };
    }
    static public function of(...$arguments): ConditionCollection
    {
        $instance = new self(...$arguments);
        return $instance;
    }
    public function addExpression(mixed $expression): void
    {
        if (is_array($expression)) {
            foreach ($expression as $key => $value) {
                if (is_numeric($key)) {
                    $this->addExpression($value);
                } else {
                    if (is_array($value)) {
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s IN ({#,...#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } elseif ($value instanceof FragmentInterface) {
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            /**
                             * By default, we assume that subquery returns a single value
                             * If not, a "field" IN ({#::}) is still supported in custom query mode
                             */
                            default => sprintf('%s = ({#::#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else if ($value === null || is_scalar($value)) {
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => match ($value === null) {
                                    true => sprintf('%s IS NULL', $quoted),
                                    default => sprintf('%s = {#}', $quoted)
                                }
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else {
                        throw new InvalidArgumentException('Unsupported condition type');
                    }
                }
            }
        } elseif ($expression instanceof FragmentInterface) {
            $this->append($expression);
        } else {
            $this->append(new Fragment($expression, []));
        }
    }
}

