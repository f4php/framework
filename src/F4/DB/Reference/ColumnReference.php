<?php

declare(strict_types=1);

namespace F4\DB\Reference;

use InvalidArgumentException;
use F4\DB;
use F4\DB\Reference\SimpleReference;

use function sprintf;

/**
 * 
 * ColumnReference is a class used to detect column references and convert them to delimeted identifiers
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class ColumnReference extends SimpleReference
{
    public const string IDENTIFIER_PATTERN = '((?<table>[a-zA-Z_][a-zA-Z0-9_]{0,62})\s*\.\s*)?(?<column>[a-zA-Z_][a-zA-Z0-9_]{0,62})';
    protected function extractDelimitedIdentifier($matches): string
    {
        if (empty($matches['column'])) {
            throw new InvalidArgumentException('Cannot locate column identifier');
        }
        return match (empty($matches['table'])) {
            true => sprintf('%s', DB::escapeIdentifier($matches['column'])),
            default => sprintf('%s.%s', DB::escapeIdentifier($matches['table']), DB::escapeIdentifier($matches['column']))
        };
    }
}

