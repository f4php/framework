<?php

declare(strict_types=1);

namespace F4\Tests\DB;
use PHPUnit\Framework\TestCase;

use F4\DB\Fragment;
use InvalidArgumentException;

final class FragmentTest extends TestCase
{
    public function testNames(): void
    {
        $fragment = new Fragment();
        $fragment->setName('test_name');
        $this->assertSame('test_name', $fragment->getName());
    }
    public function testParametersBasics(): void
    {
        $fragment = new Fragment();
        $fragment->setQuery('SELECT * FROM T WHERE "fieldA" = {#} OR "fieldB" = {#}', ['a', 'b']);
        $preparedStatement = $fragment->getPreparedStatement();
        $this->assertSame('SELECT * FROM T WHERE "fieldA" = $1 OR "fieldB" = $2', $preparedStatement->query);
        $this->assertSame('a', $preparedStatement->parameters[0]);
        $this->assertSame('b', $preparedStatement->parameters[1]);
    }
    public function testCommaParameters(): void
    {
        $fragment = new Fragment();
        $fragment->setQuery('SELECT * FROM T WHERE "fieldA" IN ({#,...#})', [['a', 'b', 'c']]);
        $preparedStatement = $fragment->getPreparedStatement();
        $this->assertSame('SELECT * FROM T WHERE "fieldA" IN ($1,$2,$3)', $preparedStatement->query);
        $this->assertSame('a', $preparedStatement->parameters[0]);
        $this->assertSame('b', $preparedStatement->parameters[1]);
        $this->assertSame('c', $preparedStatement->parameters[2]);
    }
    public function testMixedParameters(): void
    {
        $fragment = new Fragment();
        $fragment->setQuery('SELECT * FROM T WHERE "fieldA" IN ({#,...#}), "fieldB" = {#}', [['a', 'b', 'c'], 'd']);
        $preparedStatement = $fragment->getPreparedStatement();
        $this->assertSame('SELECT * FROM T WHERE "fieldA" IN ($1,$2,$3), "fieldB" = $4', $preparedStatement->query);
        $this->assertSame('a', $preparedStatement->parameters[0]);
        $this->assertSame('b', $preparedStatement->parameters[1]);
        $this->assertSame('c', $preparedStatement->parameters[2]);
        $this->assertSame('d', $preparedStatement->parameters[3]);
    }
    public function testMixedSubqueryParameters(): void
    {
        $fragment = new Fragment();
        $fragment2 = new Fragment();
        $fragment2->setQuery('SELECT "value" FROM T2 WHERE "value" > {#}', [7]);
        $fragment->setQuery('SELECT * FROM T WHERE "fieldA" = ({#::#}), "fieldB" = {#}, "fieldC" IN ({#,...#})', [$fragment2, 'e', ['a', 'b', 'c', 'd']]);
        $preparedStatement = $fragment->getPreparedStatement();
        $this->assertSame('SELECT * FROM T WHERE "fieldA" = (SELECT "value" FROM T2 WHERE "value" > $1), "fieldB" = $2, "fieldC" IN ($3,$4,$5,$6)', $preparedStatement->query);
        $this->assertSame(7, $preparedStatement->parameters[0]);
        $this->assertSame('e', $preparedStatement->parameters[1]);
        $this->assertSame('a', $preparedStatement->parameters[2]);
        $this->assertSame('b', $preparedStatement->parameters[3]);
        $this->assertSame('c', $preparedStatement->parameters[4]);
        $this->assertSame('d', $preparedStatement->parameters[5]);
    }
    public function testStrictParametersValidation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $fragment = new Fragment();
        $fragment->setQuery('"field" = {#}, "field" IN {#,...#}', [['d', 'e', 'f'], 'abc']);
    }

}