<?php

declare(strict_types=1);

namespace F4\DB\Adapter;

use F4\DB\PreparedStatement;

interface AdapterInterface
{
    public function execute(PreparedStatement $statement, ?int $stopAfter = null): mixed;
    public function enumerateParameters(int $index): string;
    public function getEscapedValue(mixed $value): string;
}

