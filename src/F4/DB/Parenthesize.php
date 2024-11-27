<?php

declare(strict_types=1);

namespace F4\DB;

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
        array_map(function($argument) {
            $this->append($argument);
        }, $arguments);
    }
    public function getQuery(): string {
        return sprintf("(%s)", implode(static::GLUE, array_map(function(FragmentInterface $fragment): string {
            return $fragment->getQuery();
        }, $this->fragments)));
    }

}

