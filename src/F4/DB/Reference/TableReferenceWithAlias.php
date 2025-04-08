<?php

declare(strict_types=1);

namespace F4\DB\Reference;

use InvalidArgumentException;
use F4\DB;
use F4\DB\Reference\SimpleReference;

use function sprintf;

/**
 * 
 * TableReferenceWithAlias is a class used to detect table references with optional alias and convert them to delimeted identifiers
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class TableReferenceWithAlias extends SimpleReference
{
    public const string IDENTIFIER_PATTERN = '(?<table>[a-zA-Z_][a-zA-Z0-9_]{0,62})(\s+(?<alias>[a-zA-Z_][a-zA-Z0-9_]{0,62}))?';
    protected function extractDelimitedIdentifier($matches): string
    {
        if (empty($matches['table'])) {
            throw new InvalidArgumentException('Cannot locate table identifier');
        }
        return match (empty($matches['alias'])) {
            true => sprintf('%s', DB::escapeIdentifier($matches['table'])),
            default => sprintf('%s AS %s', DB::escapeIdentifier($matches['table']), DB::escapeIdentifier($matches['alias'])),
        };
    }
}

