<?php

declare(strict_types=1);

namespace F4;

use F4\AbstractConfig;

class Config extends AbstractConfig {
    public const string DB_ADAPTER_CLASS = \F4\Tests\DB\MockAdapter::class;
}