<?php

declare(strict_types=1);

namespace F4\Tests;
use PHPUnit\Framework\TestCase;

use InvalidArgumentException;
use F4\DB;
use F4\DB\Fragment;
use F4\DB\AnyConditionCollection as any;
use F4\DB\ConditionCollection as all;

final class DBTest extends TestCase
{
    public function testSelect(): void
    {
        $db1 = DB::select(['t.fieldA', 't.fieldB b', 'fieldC', 'fieldD' => 5, 'fieldE' => DB::select(7), 'fieldF' => DB::select(['f' => 'abcde']), 'fieldG' => DB::select(['fieldH' => DB::select('t2.fieldI i')])]);
        $this->assertSame('SELECT "t"."fieldA", "t"."fieldB" AS "b", "fieldC", $1 AS "fieldD", (SELECT 7) AS "fieldE", (SELECT $2 AS "f") AS "fieldF", (SELECT (SELECT "t2"."fieldI" AS "i") AS "fieldH") AS "fieldG"', $db1->getPreparedStatement()->query);
        $this->assertSame(5, $db1->getPreparedStatement()->parameters[0]);
        $this->assertSame('abcde', $db1->getPreparedStatement()->parameters[1]);
        $db2 = DB::select(['fieldA' => [1, 2, 'abc']]);
        $this->assertSame('SELECT ($1,$2,$3) AS "fieldA"', $db2->getPreparedStatement()->query);
        $this->assertSame(1, $db2->getPreparedStatement()->parameters[0]);
        $this->assertSame(2, $db2->getPreparedStatement()->parameters[1]);
        $this->assertSame('abc', $db2->getPreparedStatement()->parameters[2]);
    }
    public function testSelectWithPlaceholders(): void
    {
        $db1 = DB::select(['{#} AS "fieldA"' => 5]);
        $this->assertSame('SELECT $1 AS "fieldA"', $db1->getPreparedStatement()->query);
        $this->assertSame(5, $db1->getPreparedStatement()->parameters[0]);
        $db2 = DB::select(['({#,...#}) AS "fieldB"' => [1, 2, 'abc']]);
        $this->assertSame('SELECT ($1,$2,$3) AS "fieldB"', $db2->getPreparedStatement()->query);
        $this->assertSame(1, $db2->getPreparedStatement()->parameters[0]);
        $this->assertSame(2, $db2->getPreparedStatement()->parameters[1]);
        $this->assertSame('abc', $db2->getPreparedStatement()->parameters[2]);
        $db3 = DB::select(new Fragment('{#} + {#} AS "fieldA"', [6, 7]));
        $this->assertSame('SELECT $1 + $2 AS "fieldA"', $db3->getPreparedStatement()->query);
        $this->assertSame(6, $db3->getPreparedStatement()->parameters[0]);
        $this->assertSame(7, $db3->getPreparedStatement()->parameters[1]);
    }
    public function testFrom(): void
    {
        $db1 = DB::select()->from('table');
        $this->assertSame('SELECT * FROM "table"', $db1->getPreparedStatement()->query);
        $db2 = DB::select()->from('table t');
        $this->assertSame('SELECT * FROM "table" AS "t"', $db2->getPreparedStatement()->query);
        $db3 = DB::select()->from(['t1' => DB::select()->from('table2 t2')]);
        $this->assertSame('SELECT * FROM (SELECT * FROM "table2" AS "t2") AS "t1"', $db3->getPreparedStatement()->query);
    }
    public function testFromWithPlaceholders(): void
    {
        $db1 = DB::select()->from(['({#::#}) AS "t1"' => DB::select()->from('table2 t2')]);
        $this->assertSame('SELECT * FROM (SELECT * FROM "table2" AS "t2") AS "t1"', $db1->getPreparedStatement()->query);
    }
    public function testInvalidFromSubquery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DB::select()->from(['t1' => DB::select()->from(DB::select()->from('t3'))]);
    }
    public function testInvalidFromArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DB::select()->from(['t1' => [1, 2, 3]]);
    }
    public function testInvalidFromArrayWithPlaceholders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DB::select()->from(['({#,...#} AS "t1"' => [1, 2, 3]]);
    }
    public function testInvalidFromScalar(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DB::select()->from(['t1' => 1]);
    }
    public function testInvalidFromScalarWithPlaceholders(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DB::select()->from(['({#}) AS "t1"' => 1]);
    }
    public function testSimpleWhere(): void
    {
        $db1 = DB::select()->from('table')->where(['a' => 5, 'b' => ['a', 4, 'def'], '"g" > {#}' => 6]);
        $this->assertSame('SELECT * FROM "table" WHERE "a" = $1 AND "b" IN ($2,$3,$4) AND "g" > $5', $db1->getPreparedStatement()->query);
        $this->assertSame(5, $db1->getPreparedStatement()->parameters[0]);
        $this->assertSame('a', $db1->getPreparedStatement()->parameters[1]);
        $this->assertSame(4, $db1->getPreparedStatement()->parameters[2]);
        $this->assertSame('def', $db1->getPreparedStatement()->parameters[3]);
        $this->assertSame(6, $db1->getPreparedStatement()->parameters[4]);
        $db2 = DB::select()->from('table')->where(['z' => true])->where(any::of(['a' => 5, 'b' => ['a', 4, 'def'], '"g" > {#}' => 6]));
        $this->assertSame('SELECT * FROM "table" WHERE "z" = $1 AND ("a" = $2 OR "b" IN ($3,$4,$5) OR "g" > $6)', $db2->getPreparedStatement()->query);
        $this->assertSame(true, $db2->getPreparedStatement()->parameters[0]);
        $this->assertSame(5, $db2->getPreparedStatement()->parameters[1]);
        $this->assertSame('a', $db2->getPreparedStatement()->parameters[2]);
        $this->assertSame(4, $db2->getPreparedStatement()->parameters[3]);
        $this->assertSame('def', $db2->getPreparedStatement()->parameters[4]);
        $this->assertSame(6, $db2->getPreparedStatement()->parameters[5]);
        $db3 = DB::select()->from('table')->where([]);
        $this->assertSame('SELECT * FROM "table"', $db3->getPreparedStatement()->query);
        $db4 = DB::select()->from('table')->where(['a' => 1])->where(any::of(['b' => 2, 'c' => ['3', 4, 'def'], all::of(['"g" > {#}' => 5, 'h' => 6])]));
        $this->assertSame('SELECT * FROM "table" WHERE "a" = $1 AND ("b" = $2 OR "c" IN ($3,$4,$5) OR ("g" > $6 AND "h" = $7))', $db4->getPreparedStatement()->query);
        $db5 = DB::select()->from('table')->where(['a' => null]);
        $this->assertSame('SELECT * FROM "table" WHERE "a" IS NULL', $db5->getPreparedStatement()->query);
    }
    public function testSimpleWith(): void
    {
        $db1 = DB::with(['table' => DB::select()->from('t')])->select()->from('table');
        $this->assertSame('WITH "table" AS (SELECT * FROM "t") SELECT * FROM "table"', $db1->getPreparedStatement()->query);
    }

    public function testUnionExceptIntersect(): void
    {
        $db1 = DB::select()->from('t1')->union()->select()->from('t2');
        $db2 = DB::select()->from('t1')->unionAll()->select()->from('t2');
        $db3 = DB::select()->from('t1')->intersect()->select()->from('t2');
        $db4 = DB::select()->from('t1')->intersectAll()->select()->from('t2');
        $db5 = DB::select()->from('t1')->except()->select()->from('t2');
        $db6 = DB::select()->from('t1')->exceptAll()->select()->from('t2');
        $db7 = DB::select()->from('t1')->union()->select()->from('t2')->intersect()->select()->from('t3')->except()->select()->from('t4');
        $db8 = DB::select()->from('t1')->where(['a'=>1])->union()->select()->from('t2')->where(['b'=>2]);
        $this->assertSame('SELECT * FROM "t1" UNION SELECT * FROM "t2"', $db1->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" UNION ALL SELECT * FROM "t2"', $db2->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" INTERSECT SELECT * FROM "t2"', $db3->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" INTERSECT ALL SELECT * FROM "t2"', $db4->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" EXCEPT SELECT * FROM "t2"', $db5->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" EXCEPT ALL SELECT * FROM "t2"', $db6->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" UNION SELECT * FROM "t2" INTERSECT SELECT * FROM "t3" EXCEPT SELECT * FROM "t4"', $db7->getPreparedStatement()->query);
        $this->assertSame('SELECT * FROM "t1" WHERE "a" = $1 UNION SELECT * FROM "t2" WHERE "b" = $2', $db8->getPreparedStatement()->query);
    }

    public function testGroupByHaving(): void
    {
        $db1 = DB::select()->from('t1')->group('a', 'b', 'c')->having(['d > {#}' => 7]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY ("a", "b", "c") HAVING d > $1', $db1->getPreparedStatement()->query);
        $db2 = DB::select()->from('t1')->groupBy(['a', 'b', 'c'])->having(['d > {#}' => 7]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY ("a", "b", "c") HAVING d > $1', $db2->getPreparedStatement()->query);
        $this->assertSame(7, $db2->getPreparedStatement()->parameters[0]);
    }

    public function testInsert(): void
    {
        $db1 = DB::insert()->into('table1 t1')->values(['fieldA' => 1, 'fieldB' => 'abc', 'fieldC' => 'defg'])->where(['fieldD' => 5, '"fieldE" > {#}' => 7])->onConflict('fieldF')->doUpdateSet(['fieldG' => 2])->returning('fieldH');
        $this->assertSame('INSERT INTO "table1" AS "t1" ("fieldA", "fieldB", "fieldC") VALUES ($1, $2, $3) WHERE "fieldD" = $4 AND "fieldE" > $5 ON CONFLICT ("fieldF") DO UPDATE SET "fieldG" = $6 RETURNING "fieldH"', $db1->getPreparedStatement()->query);
        $this->assertSame(1, $db1->getPreparedStatement()->parameters[0]);
        $this->assertSame('abc', $db1->getPreparedStatement()->parameters[1]);
        $this->assertSame('defg', $db1->getPreparedStatement()->parameters[2]);
        $this->assertSame(5, $db1->getPreparedStatement()->parameters[3]);
        $this->assertSame(7, $db1->getPreparedStatement()->parameters[4]);
        $this->assertSame(2, $db1->getPreparedStatement()->parameters[5]);
        $db2 = DB::insert()->into('table1 t1')->values(['fieldA' => 1])->onConflict('fieldF')->doNothing();
        $this->assertSame('INSERT INTO "table1" AS "t1" ("fieldA") VALUES ($1) ON CONFLICT ("fieldF") DO NOTHING', $db2->getPreparedStatement()->query);
        $db3 = DB::insert()->into('table1 t1')->values(['fieldA' => ['1 + {#}' => 2], 'fieldB' => 3])->onConflict('fieldF')->doNothing();
        $this->assertSame('INSERT INTO "table1" AS "t1" ("fieldA", "fieldB") VALUES (1 + $1, $2) ON CONFLICT ("fieldF") DO NOTHING', $db3->getPreparedStatement()->query);
    }
    public function testUpdate(): void
    {
        $db1 = DB::update('table1 t1')->set(['fieldA' => 2, 'fieldB' => 3]);
        $this->assertSame('UPDATE "table1" AS "t1" SET "fieldA" = $1, "fieldB" = $2', $db1->getPreparedStatement()->query);
        $this->assertSame(2, $db1->getPreparedStatement()->parameters[0]);
        $this->assertSame(3, $db1->getPreparedStatement()->parameters[1]);
    }
    public function testOrderBy(): void
    {
        $db1 = DB::select()->from('t1')->order(['a' => 'asc', 'b' => 'desc ']);
        $this->assertSame('SELECT * FROM "t1" ORDER BY "a" ASC, "b" DESC', $db1->getPreparedStatement()->query);
    }
    public function testLimitOffset(): void
    {
        $db1 = DB::select()->from('t1')->limit(10, 0);
        $this->assertSame('SELECT * FROM "t1" LIMIT 10 OFFSET 0', $db1->getPreparedStatement()->query);
        $db2 = DB::select()->from('t1')->limit(10)->offset(0);
        $this->assertSame('SELECT * FROM "t1" LIMIT 10 OFFSET 0', $db2->getPreparedStatement()->query);
    }
    public function testJoins(): void
    {
        $db1 = DB::select()->from('table1 t1')->join("table2 t2")->using('fieldA', 'fieldB');
        $this->assertSame('SELECT * FROM "table1" AS "t1" JOIN "table2" AS "t2" USING ("fieldA", "fieldB")', $db1->getPreparedStatement()->query);
        $db2 = DB::select()->from('table1 t1')->leftJoin("table2 t2")->using('fieldA', 'fieldB');
        $this->assertSame('SELECT * FROM "table1" AS "t1" LEFT JOIN "table2" AS "t2" USING ("fieldA", "fieldB")', $db2->getPreparedStatement()->query);
        $db3 = DB::select()->from('table1 t1')->leftOuterJoin("table2 t2")->using('fieldA', 'fieldB');
        $this->assertSame('SELECT * FROM "table1" AS "t1" LEFT OUTER JOIN "table2" AS "t2" USING ("fieldA", "fieldB")', $db3->getPreparedStatement()->query);
        $db4 = DB::select()->from('table1 t1')->rightJoin("table2 t2")->using('fieldA', 'fieldB');
        $this->assertSame('SELECT * FROM "table1" AS "t1" RIGHT JOIN "table2" AS "t2" USING ("fieldA", "fieldB")', $db4->getPreparedStatement()->query);
        $db5 = DB::select()->from('table1 t1')->rightOuterJoin("table2 t2")->using('fieldA', 'fieldB');
        $this->assertSame('SELECT * FROM "table1" AS "t1" RIGHT OUTER JOIN "table2" AS "t2" USING ("fieldA", "fieldB")', $db5->getPreparedStatement()->query);
        $db1 = DB::select()->from('table1 t1')->join("table2 t2")->on(['fieldA' => 'abc', '"fieldB" > {#}' => 4]);
        $this->assertSame('SELECT * FROM "table1" AS "t1" JOIN "table2" AS "t2" ON "fieldA" = $1 AND "fieldB" > $2', $db1->getPreparedStatement()->query);
        $this->assertSame('abc', $db1->getPreparedStatement()->parameters[0]);
        $this->assertSame(4, $db1->getPreparedStatement()->parameters[1]);
    }
    public function testDelete(): void
    {
        $db1 = DB::delete()->from('t1')->where(['a' => 4, '"b" > {#}' => 0]);
        $this->assertSame('DELETE FROM "t1" WHERE "a" = $1 AND "b" > $2', $db1->getPreparedStatement()->query);
    }
    public function testDropTable(): void
    {
        $db1 = DB::dropTable('t1', 't2');
        $this->assertSame('DROP TABLE "t1", "t2"', $db1->getPreparedStatement()->query);
        $db2 = DB::dropTableIfExists('t1');
        $this->assertSame('DROP TABLE IF EXISTS "t1"', $db2->getPreparedStatement()->query);
        $db3 = DB::dropTableWithCascade('t1');
        $this->assertSame('DROP TABLE "t1" CASCADE', $db3->getPreparedStatement()->query);
        $db4 = DB::dropTableIfExistsWithCascade('t1');
        $this->assertSame('DROP TABLE IF EXISTS "t1" CASCADE', $db4->getPreparedStatement()->query);
    }
    public function testRaw(): void
    {
        $db1 = DB::raw(['SELECT * FROM "table" AS "t1" WHERE "a" = {#} AND "b" IN ({#,...#}) AND "c" IN ({#::#})' => ['abc', [1, 2, 3, 'def'], DB::select()->from('t2')]]);
        $this->assertSame('SELECT * FROM "table" AS "t1" WHERE "a" = $1 AND "b" IN ($2,$3,$4,$5) AND "c" IN (SELECT * FROM "t2")', $db1->getPreparedStatement()->query);
    }
    public function testConsecutiveCalls(): void
    {
        $db1 = DB::select()->from('table t1')->where(["a" => 1])->where(['b' => [1, 2, 3]]);
        $this->assertSame('SELECT * FROM "table" AS "t1" WHERE "a" = $1 AND "b" IN ($2,$3,$4)', $db1->getPreparedStatement()->query);
        $db2 = DB::insert()->into('table1 t1')->values(['fieldA' => 1, 'fieldB' => 'abc'])->values(['fieldC' => 'defg'])->where(['fieldD' => 5, '"fieldE" > {#}' => 7])->onConflict('fieldF')->doUpdateSet(['fieldG' => 2])->returning('fieldH');
        $this->assertSame('INSERT INTO "table1" AS "t1" ("fieldA", "fieldB", "fieldC") VALUES ($1, $2, $3) WHERE "fieldD" = $4 AND "fieldE" > $5 ON CONFLICT ("fieldF") DO UPDATE SET "fieldG" = $6 RETURNING "fieldH"', $db2->getPreparedStatement()->query);
        $db3 = DB::update('table1 t1')->set(['fieldA' => 2])->set(['fieldB' => 3]);
        $this->assertSame('UPDATE "table1" AS "t1" SET "fieldA" = $1, "fieldB" = $2', $db3->getPreparedStatement()->query);
        $db4 = DB::select()->from('t1')->order(['a' => 'asc'])->orderBy(['b' => 'desc ']);
        $this->assertSame('SELECT * FROM "t1" ORDER BY "a" ASC, "b" DESC', $db4->getPreparedStatement()->query);
        $db5 = DB::insert()->into('table1 t1')->values(['fieldA' => 1, 'fieldB' => 'abc', 'fieldC' => 'defg'])->where(['fieldD' => 5, '"fieldE" > {#}' => 7])->onConflict('fieldF')->doUpdateSet(['fieldG' => 2])->doUpdateSet(['fieldH' => 3])->returning('fieldH');
        $this->assertSame('INSERT INTO "table1" AS "t1" ("fieldA", "fieldB", "fieldC") VALUES ($1, $2, $3) WHERE "fieldD" = $4 AND "fieldE" > $5 ON CONFLICT ("fieldF") DO UPDATE SET "fieldG" = $6, "fieldH" = $7 RETURNING "fieldH"', $db5->getPreparedStatement()->query);
        $db6 = DB::select()->from('t1')->group('a', 'b', 'c')->having(['"d" > {#}' => 7, '"e" < {#}' => 2]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY ("a", "b", "c") HAVING "d" > $1 AND "e" < $2', $db6->getPreparedStatement()->query);
        $db7 = DB::select()->from('t1')->groupBy(['a'])->groupBy(['b', 'c'])->having(['d > {#}' => 7]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY ("a", "b", "c") HAVING d > $1', $db7->getPreparedStatement()->query);
        $db8 = DB::select()->from('t1')->groupByAll(['a'])->groupBy(['b', 'c'])->having(['d > {#}' => 7]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY ALL ("a", "b", "c") HAVING d > $1', $db8->getPreparedStatement()->query);
        $db9 = DB::select()->from('t1')->groupByDistinct(['a'])->groupBy(['b', 'c'])->having(['d > {#}' => 7]);
        $this->assertSame('SELECT * FROM "t1" GROUP BY DISTINCT ("a", "b", "c") HAVING d > $1', $db9->getPreparedStatement()->query);
    }
    public function testAsSQL(): void
    {
        $db1 = DB::select()->from('table t1')->where(["a" => 1])->where(['b' => [2, 3, 4]])->where(['d' => true, 'e' => null]);
        $this->assertSame('SELECT * FROM "table" AS "t1" WHERE "a" = 1 AND "b" IN (2,3,4) AND "d" = TRUE AND "e" IS NULL', $db1->asSQL());
        $db2 = DB::select()->from('table t1')->where(["a" => 'b'])->where(['c' => ['d', 'e', 'f']]);
        $this->assertSame('SELECT * FROM "table" AS "t1" WHERE "a" = \'b\' AND "c" IN (\'d\',\'e\',\'f\')', $db2->asSQL());
        $db3 = DB::select()->from('table t1')->where(["a" => "O'Reilly"]);
        $this->assertSame('SELECT * FROM "table" AS "t1" WHERE "a" = \'O\'\'Reilly\'', $db3->asSQL());
    }

}