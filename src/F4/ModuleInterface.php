<?php

declare(strict_types=1);

namespace F4;

use F4\Core\CoreApiInterface;

interface ModuleInterface
{
    public function __construct(CoreApiInterface &$api);
}

