<?php

declare(strict_types=1);

namespace F4\Tests\DB;
use PHPUnit\Framework\TestCase;

use F4\DB\Reference\ColumnReference;
use F4\DB\Reference\ColumnReferenceWithAlias;
use F4\DB\Reference\SimpleReference;
use F4\DB\Reference\TableReference;
use F4\DB\Reference\TableReferenceWithAlias;

final class ReferenceTest extends TestCase
{
    public function testReferences(): void
    {
        $reference0 = new SimpleReference(' someField ');
        $this->assertSame('"someField"', $reference0->delimitedIdentifier);
        $reference1 = new ColumnReference(' someField ');
        $this->assertSame('"someField"', $reference1->delimitedIdentifier);
        $reference2 = new ColumnReference(' someTable .  someField ');
        $this->assertSame('"someTable"."someField"', $reference2->delimitedIdentifier);
        $reference3 = new ColumnReferenceWithAlias('someTable .  someField  alias ');
        $this->assertSame('"someTable"."someField" AS "alias"', $reference3->delimitedIdentifier);
        $reference4 = new TableReference('someTable');
        $this->assertSame('"someTable"', $reference4->delimitedIdentifier);
        $reference5 = new TableReferenceWithAlias(' someTable t1');
        $this->assertSame('"someTable" AS "t1"', $reference5->delimitedIdentifier);
    }
}