<?php

declare(strict_types=1);

namespace F4\Core;

use function hrtime;
use function memory_get_usage;

class Profiler
{
    protected static array $snapshots = [];

    public static function init(): void {
        self::addSnapshot('App init');
    }
    public static function addSnapshot(string $description): void {
        self::$snapshots[] = [
            'memory' => memory_get_usage(real_usage: false),
            'time' => hrtime(as_number: true),
            'description' => $description
        ];
    }
    public static function getSnapshots(): array {
        return self::$snapshots;
    }
}
