<?php

declare(strict_types=1);

namespace F4\DB;

use F4\DB\PreparedStatement;

interface FragmentInterface
{
    public function setQuery(string $query, array $parameters = []): static;
    public function withPrefix(string $prefix): static;
    public function getParameters(): array;
    public function getQuery(): string;
    public function getPreparedStatement(?callable $enumeratorCallback = null): PreparedStatement;
}

