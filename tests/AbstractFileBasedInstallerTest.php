<?php

namespace alcamo\dao;

use alcamo\exception\Unsupported;
use PHPUnit\Framework\TestCase;

class MySqliteInstaller extends AbstractFileBasedInstaller
{
    public const SCRIPT_DIR = __DIR__;

    public const SCRIPT_FILE_LISTS = [
        'sqlite' => [ 'sqlite-install.sql' ]
    ];
}

class MyNoSqliteInstaller extends AbstractFileBasedInstaller
{
    public const SCRIPT_DIR = __DIR__;

    public const SCRIPT_FILE_LISTS = [
        'pgsql' => [ 'pgsql-install.sql' ]
    ];
}

class AbstractFileBasedInstallerTest extends TestCase
{
    public const DSN = 'sqlite::memory:';

    public function testInstall(): void
    {
        $dbAccessor = DbAccessor::newFromProps([ 'dsn' => static::DSN ]);

        $this->assertFalse($dbAccessor->relationExists('foo'));

        (new MySqliteInstaller($dbAccessor))->install();

        $this->assertTrue($dbAccessor->relationExists('foo'));
    }

    public function testUnsupportedException(): void
    {
        $dbAccessor = DbAccessor::newFromProps([ 'dsn' => static::DSN ]);

        $this->expectException(Unsupported::class);

        $this->expectExceptionMessage(
            '"installation with sqlite driver" not supported'
        );
        (new MyNoSqliteInstaller($dbAccessor))->install();
    }
}
