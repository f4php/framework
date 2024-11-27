<?php

declare(strict_types=1);

namespace F4\DB;

use F4\DB\FragmentInterface;
use F4\DB\PreparedStatement;

interface FragmentCollectionInterface
{
    public function append(FragmentInterface|string $fragment): static;
    public function getFragments(): array;
    public function getPreparedStatement(?callable $enumeratorFunction = null): PreparedStatement;
}

