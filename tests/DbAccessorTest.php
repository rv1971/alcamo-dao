<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class MyClass extends \StdClass
{
}

class MyDbAccessor extends DbAccessor
{
    public const RECORD_CLASS = MyClass::class;
}

class DbAccessorTest extends TestCase
{
    public const CREATE_TABLE = 'CREATE TABLE foo(msg TEXT)';

    public const INSERT = "INSERT INTO foo VALUES('Hello, world!')";

    public const SELECT = "SELECT * FROM foo";

    public const DSN = 'sqlite::memory:';

    /**
     * @dataProvider basicsProvider
     */
    public function testBasics($connection)
    {
        $accessor = new MyDbAccessor($connection);

        $this->assertTrue(
            $accessor->prepare(static::CREATE_TABLE)->execute()
        );

        $this->assertTrue(
            $accessor->prepare(static::INSERT)->execute()
        );

        $stmt = $accessor->prepare(static::SELECT);

        $this->assertTrue($stmt->execute());

        $expectedResult = new MyClass();

        $expectedResult->msg = 'Hello, world!';

        foreach ($stmt as $record) {
            $this->assertEquals($expectedResult, $record);
        }
    }

    public function basicsProvider()
    {
        return [
            'pdo' => [ new \PDO(static::DSN) ],
            'dbaccessor' => [ new DbAccessor(static::DSN) ],
            'assoc' => [ [ 'dsn' => static::DSN ] ],
            'array' => [ [ static::DSN ] ],
            'string' => [ static::DSN ],
        ];
    }

    public function testException()
    {
        $accessor = new DbAccessor(static::DSN);

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('HY000');

        $accessor->prepare('select * from bar');
    }
}
