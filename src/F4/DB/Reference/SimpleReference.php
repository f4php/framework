<?php

declare(strict_types=1);

namespace F4\DB\Reference;

use Composer\Pcre\Regex;
use InvalidArgumentException;
use F4\DB\Reference\ReferenceInterface;

use function sprintf;

/**
 * 
 * SimpleReference is used for simple references like isolated field or table names
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class SimpleReference implements ReferenceInterface
{
    public readonly ?string $delimitedIdentifier;

    /**
     *
     * Currently (Nov 2024) the maximum length of an identifier is 63 characters
     *
     * https://www.postgresql.org/docs/current/sql-syntax-lexical.html#SQL-SYNTAX-IDENTIFIERS
     *
     */
    public const string IDENTIFIER_PATTERN = '(?<identifier>[a-zA-Z_][a-zA-Z0-9_]{0,62})';

    public function __construct(string $reference)
    {
        $this->delimitedIdentifier = match (($match = Regex::replaceCallback('/' . static::IDENTIFIER_PATTERN . '$/Anu', $this->extractDelimitedIdentifier(...), mb_trim($reference)))->matched) {
            true => $match->result,
            default => null
        };
    }
    protected function extractDelimitedIdentifier($matches): string
    {
        if (empty($matches['identifier'])) {
            throw new InvalidArgumentException('Cannot locate identifier');
        }
        return sprintf('"%s"', $matches['identifier']);
    }
}

