<?php

declare(strict_types=1);

namespace F4\Tests\DB;

use F4\DB\Adapter\AdapterInterface;
use F4\DB\PreparedStatement;

final class MockAdapter implements AdapterInterface
{
    public function __construct() {}

    public function execute(PreparedStatement $statement): mixed
    {
        return [];
    }
    public function enumerateParameters(int $index): string
    {
        return sprintf('$%d', $index);
    }

}