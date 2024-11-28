<?php

declare(strict_types=1);

namespace F4\Tests\DB;
use PHPUnit\Framework\TestCase;

use F4\DB\Fragment;
use F4\DB\FragmentCollection;
use InvalidArgumentException;

final class FragmentCollectionTest extends TestCase
{
    public function testSimpleFragments(): void {
        $fragmentCollection = (new FragmentCollection())
            ->append(new Fragment('SELECT'))
            ->append(new Fragment('fieldA'));
        $this->assertSame('SELECT fieldA', $fragmentCollection->getPreparedStatement()->query);
    }

}