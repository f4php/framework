<?php

declare(strict_types=1);

namespace F4\DB;

use InvalidArgumentException;
use F4\DB\Reference\SimpleReference;
use F4\DB\Reference\TableReferenceWithAlias;

use function array_map;
use function is_numeric;
use function mb_trim;

/**
 * 
 * Parenthesize is simple wrapper that adds parenthesis to its arguments
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class Parenthesize extends FragmentCollection
{
    public function __construct(...$arguments) {
        \array_map(function($argument) {
            $this->append($argument);
        }, $arguments);
    }
    public function getQuery(): string {
        return sprintf("(%s)", implode(static::GLUE, array_map(function(FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments)));
    }

}

