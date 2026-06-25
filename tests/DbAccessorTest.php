<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class DbAccessorTest extends TestCase
{
    public const CREATION_SCRIPT = [
        'CREATE TABLE foo(msg TEXT)',
        "INSERT INTO foo VALUES('Hello, world!')"
    ];

    public const SELECT_STMT = "SELECT * FROM foo";

    public const DSN = 'sqlite::memory:';

    public function testBasics()
    {
        $accessor = DbAccessor::newFromProps([ 'dsn' => static::DSN ]);

        $accessor->executeScript(static::CREATION_SCRIPT);

        $stmt = $accessor->prepare(static::SELECT_STMT);

        $this->assertInstanceof(Statement::class, $stmt);

        $this->assertTrue($stmt->execute());

        $expectedResult = new \StdClass();

        $expectedResult->msg = 'Hello, world!';

        $this->assertEquals($expectedResult, $stmt->fetch());
    }

    public function testException()
    {
        $accessor = DbAccessor::newFromDsn(static::DSN);

        $this->expectException(\PDOException::class);
        $this->expectExceptionMessage('HY000');

        $accessor->prepare('select * from bar');
    }
}
