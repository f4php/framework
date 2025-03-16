<?php

declare(strict_types=1);

namespace F4\DB;

use function array_filter;
use function array_map;
use function implode;
use function sprintf;

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
        array_map(function($argument): void {
            $this->append($argument);
        }, $arguments);
    }
    public function getQuery(): string {
        return match(empty($query = implode(static::GLUE, array_filter(array_map(function (FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments))))) {
            true => '',
            default => match($this->prefix) {
                null => sprintf("(%s)", $query),
                default => sprintf('%s (%s)', $this->prefix, $query)
            }
        };
    }

}

