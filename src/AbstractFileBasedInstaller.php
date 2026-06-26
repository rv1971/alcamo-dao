<?php

namespace alcamo\dao;

/**
 * @brief Simple installer that executes a list of SQL files
 *
 * @date last reviewed 2026-06-26
 */
abstract class AbstractFileBasedInstaller implements InstallerInterface
{
    /// Directory where installation scripts are stored
    public const SCRIPT_DIR = null;

    /// List of scripts to execute
    public const SCRIPT_FILES = [ 'install.sql' ];

    private $dbAccessor_;

    public function __construct(DbAccessor $dbAccessor)
    {
        $this->dbAccessor_ = $dbAccessor;
    }

    public function getDbAccessor(): DbAccessor
    {
        return $this->dbAccessor_;
    }

    public function install()
    {
        foreach (static::SCRIPT_FILES as $file) {
            $this->dbAccessor_->executeSqlFile(
                static::SCRIPT_DIR . DIRECTORY_SEPARATOR . $file
            );
        }
    }
}
