<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class MyClass extends \StdClass
{
}

class MyRelationAccessor extends RelationAccessor
{
    public const RELATION_NAME = 'foo';

    public const FETCH_CLASS = MyClass::class;
}

/* This also tests class Statement. */
class RelationAccessorTest extends TestCase
{
    public const CREATE_TABLE =
        'CREATE TABLE /*_*/foo(bar INTEGER, baz INTEGER, qux INTEGER)';

    public const INSERTS = [
        "INSERT INTO /*_*/foo VALUES(9, 9, 9)",
        "INSERT INTO /*_*/foo VALUES(8, 7, 6)",
        "INSERT INTO bar_foo VALUES(8, 6, 0);",
        "INSERT INTO bar_foo VALUES(8, 7, 5);",
        "INSERT INTO bar_foo VALUES(8, 6, 1)"
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
        $accessor = MyRelationAccessor::newFromProps(
            [
                'dsn' => static::DSN,
                'namePrefix' => 'bar_'
            ]
        );

        $this->assertSame('foo', $accessor->getRelationName());

        $this->assertTrue(
            $accessor->prepare(static::CREATE_TABLE)->execute()
        );

        $this->assertSame(0, count($accessor));

        $accessor->getDbAccessor()->executeScript(static::INSERTS);

        $i = 0;

        $this->assertSame(count(static::EXPECTED), count($accessor));

        foreach ($accessor as $record) {
            $this->assertInstanceof(MyClass::class, $record);
            $this->assertEquals(
                static::EXPECTED[$i++],
                get_object_vars($record)
            );
        }
    }
}
