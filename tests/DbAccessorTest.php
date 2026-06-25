<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class DbAccessorTest extends TestCase
{
    public const SELECT_STMT = "SELECT * FROM my_foo";

    public const DSN = 'sqlite::memory:';

    public function testBasics()
    {
        $accessor = DbAccessor::newFromProps(
            [ 'dsn' => static::DSN, 'tablePrefix' => 'my_' ]
        );

        $accessor->executeSqlFile(__DIR__ . DIRECTORY_SEPARATOR . 'create.sql');

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
