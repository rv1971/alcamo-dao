<?php

namespace alcamo\dao;

use PHPUnit\Framework\TestCase;

class MyInstaller extends AbstractFileBasedInstaller
{
    public const SCRIPT_DIR = __DIR__;
}

/* This also tests class AbstractFileBasedInstaller. */
class DbAccessorTest extends TestCase
{
    public const SELECT_STMT = "SELECT * FROM my_foo";

    public const DSN = 'sqlite::memory:';

    public function testBasics()
    {
        $accessor = DbAccessor::newFromProps(
            [ 'dsn' => static::DSN, 'tablePrefix' => 'my_' ]
        );

        (new MyInstaller($accessor))->install();

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
