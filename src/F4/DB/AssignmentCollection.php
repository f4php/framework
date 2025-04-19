<?php

declare(strict_types=1);

namespace F4\DB;

use F4\DB\Reference\ColumnReference;
use InvalidArgumentException;

use function is_array;
use function is_numeric;
use function is_scalar;

/**
 * 
 * AssignmentCollection is an abstraction of sql expressions allowed inside a "WHERE" part of a statement
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class AssignmentCollection extends FragmentCollection
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
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s = ARRAY [{#,...#}]', $quoted)
                        };
                        $value = match(count(Fragment::extractPlaceholders($query)) > 1) {
                            true => $value,
                            default => [$value]
                        };
                        $this->append(new Fragment($query, $value));
                    } else if ($value instanceof FragmentInterface) {
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s = ({#::#})', $quoted)
                        };
                        $this->append(new Fragment($query, [$value]));
                    } else if ($value === null || is_scalar($value)) {
                        $query = match ($quoted = (new ColumnReference($key))->delimitedIdentifier) {
                            null => $key,
                            default => sprintf('%s = {#}', $quoted)
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

