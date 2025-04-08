<?php

declare(strict_types=1);

namespace F4\DB\Reference;

/**
 * 
 * ReferenceInterface ensures compatibility between several supported reference implementations
 * 
 * @package F4\DB
 * @author Dennis Kreminsky <dennis@kreminsky.com>
 * 
 */
interface ReferenceInterface
{
    public ?string $delimitedIdentifier { get;
    }
}