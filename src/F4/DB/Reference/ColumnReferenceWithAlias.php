<?php

declare(strict_types=1);

namespace F4\DB\Reference;

use InvalidArgumentException;
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
class ColumnReferenceWithAlias extends SimpleReference
{
    public const string IDENTIFIER_PATTERN = '((?<table>[a-zA-Z_][a-zA-Z0-9_]{0,62})\s*\.\s*)?(?<column>[a-zA-Z_][a-zA-Z0-9_]{0,62})(\s+(?<alias>[a-zA-Z_][a-zA-Z0-9_]{0,62}))?';
    protected function extractDelimitedIdentifier($matches): string
    {
        if (empty($matches['column'])) {
            throw new InvalidArgumentException('Cannot locate column identifier');
        }
        return
            (empty($matches['table']) ? '' : sprintf('"%s".', $matches['table'])) .
            sprintf('"%s"', $matches['column']) .
            (empty($matches['alias']) ? '' : sprintf(' AS "%s"', $matches['alias']));
    }
}

