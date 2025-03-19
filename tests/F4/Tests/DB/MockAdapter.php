<?php

declare(strict_types=1);

namespace F4\Tests\DB;

use Composer\Pcre\Preg;
use F4\DB\Adapter\AdapterInterface;
use F4\DB\PreparedStatement;
use InvalidArgumentException;

use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_scalar;
use function sprintf;

final class MockAdapter implements AdapterInterface
{
    public function __construct() {}

    public function execute(PreparedStatement $statement, ?int $stopAfter = null): mixed
    {
        return [];
    }
    public function enumerateParameters(int $index): string
    {
        return sprintf('$%d', $index);
    }
    public function getEscapedValue(mixed $value): string
    {
        return match ($value === null) {
            true => 'NULL',
            default => match (is_bool($value)) {
                    true => $value ? 'TRUE' : 'FALSE',
                    default => match (is_int($value) || is_float($value)) {
                            true => (string) $value,
                            default => match ($value instanceof DateTime) {
                                    true => $value->format('Y-m-d H:i:s'),
                                    default => match (is_scalar($value)) {
                                            true => sprintf("'%s'", Preg::replace(pattern: "/'/u", replacement: "''", subject: $value)),
                                            default => throw new InvalidArgumentException('Unsupported parameter type')
                                        }
                                }
                        }
                }
        };
    }

}