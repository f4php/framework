<?php

declare(strict_types=1);

namespace F4;

use F4\AbstractConfig;

class Config extends AbstractConfig {
    // public const bool DEBUG_MODE = true;
    public const array TEMPLATE_PATHS = [
        __DIR__ . ''
    ];
}