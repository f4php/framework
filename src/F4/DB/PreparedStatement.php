<?php

declare(strict_types=1);

namespace F4\DB;

/**
 * 
 * PreparedStatement is an immutable representation of arbitrary prepared SQL statement (query) and its parameters.
 *
 * Its main purpose is to contain a combination of two tightly coupled but different data types essential for SQL query execution.
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
class PreparedStatement
{
    public function __construct(public readonly string $query, public readonly array $parameters) {}
}

