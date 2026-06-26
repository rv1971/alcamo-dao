<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class MyInstaller extends AbstractFileBasedInstaller
{
    public const SCRIPT_DIR = __DIR__;

    public const SCRIPT_FILE_LISTS = [
        '*' => [ 'sqlite-install.sql' ]
    ];
}

class DbAccessorTest extends TestCase
{
    public const SELECT_STMT = "SELECT * FROM my_foo";

    public const DSN = 'sqlite::memory:';

    public function testBasics()
    {
        $dbAccessor = DbAccessor::newFromProps(
            [ 'dsn' => static::DSN, 'namePrefix' => 'my_' ]
        );

        $this->assertSame('sqlite', $dbAccessor->getDriverName());

        $this->assertFalse($dbAccessor->relationExists('foo'));

        (new MyInstaller($dbAccessor))->install();

        $this->assertTrue($dbAccessor->relationExists('foo'));

        $stmt = $dbAccessor->prepare(static::SELECT_STMT);

        $this->assertInstanceof(Statement::class, $stmt);

        $this->assertTrue($stmt->execute());

        $expectedResult = new \StdClass();

        $expectedResult->msg = 'Hello, world!';

        $this->assertEquals($expectedResult, $stmt->fetch());
    }

    public function testException()
    {
        $dbAccessor = DbAccessor::newFromDsn(static::DSN);

        $this->expectException(\PDOException::class);

        $this->expectExceptionMessage(
            'SQLSTATE[HY000]: General error: 1 no such table: bar'
        );

        $dbAccessor->prepare('select * from bar');
    }
}
