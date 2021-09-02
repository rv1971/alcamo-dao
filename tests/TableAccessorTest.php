<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class TableAccessorTest extends TestCase
{
    public const CREATE_TABLE =
        'CREATE TABLE foo(bar INTEGER, baz INTEGER, qux INTEGER)';

    public const INSERTS = [
        "INSERT INTO foo VALUES(9, 9, 9)",
        "INSERT INTO foo VALUES(8, 7, 6)",
        "INSERT INTO foo VALUES(8, 6, 0)",
        "INSERT INTO foo VALUES(8, 7, 5)",
        "INSERT INTO foo VALUES(8, 6, 1)"
    ];

    public const EXPECTED = [
        [ 'bar' => 8, 'baz' => 6, 'qux' => 0 ],
        [ 'bar' => 8, 'baz' => 6, 'qux' => 1 ],
        [ 'bar' => 8, 'baz' => 7, 'qux' => 5 ],
        [ 'bar' => 8, 'baz' => 7, 'qux' => 6 ],
        [ 'bar' => 9, 'baz' => 9, 'qux' => 9 ],
    ];

    public const DSN = 'sqlite::memory:';

    public function testBasics()
    {
        $accessor = new TableAccessor(static::DSN, 'foo');

        $this->assertSame('foo', $accessor->getTableName());

        $this->assertTrue(
            $accessor->prepare(static::CREATE_TABLE)->execute()
        );

        foreach (static::INSERTS as $insert) {
            $this->assertTrue($accessor->prepare($insert)->execute());
        }

        $i = 0;

        foreach ($accessor as $record) {
            $this->assertEquals((object)static::EXPECTED[$i++], $record);
        }
    }
}
