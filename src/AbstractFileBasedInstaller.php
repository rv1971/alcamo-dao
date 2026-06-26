<?php

namespace alcamo\dao;

use alcamo\exception\Unsupported;

/**
 * @brief Simple installer that executes a list of SQL files
 *
 * @date last reviewed 2026-06-26
 */
abstract class AbstractFileBasedInstaller implements InstallerInterface
{
    /// Directory where installation scripts are stored
    public const SCRIPT_DIR = null;

    /**
     * @brief Map of driver names to lists of scripts to excecute
     *
     * The key `*` represents any drivers not explicitely listed.
     */
    public const SCRIPT_FILE_LISTS = [ '*' => [ 'install.sql' ] ];

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
        $files = static::SCRIPT_FILE_LISTS[$this->dbAccessor_->getDriverName()]
            ?? static::SCRIPT_FILE_LISTS['*']
            ?? null;

        if (!isset($files)) {
            /** @throw alcamo::exception::Unsupported if no installation files
             *  exist for the chosen driver. */
            throw (new Unsupported())->setMessageContext(
                [
                    'feature' => 'installation with '
                        . $this->dbAccessor_->getDriverName() . ' driver'
                ]
            );
        }

        foreach ($files as $file) {
            $this->dbAccessor_->executeSqlFile(
                static::SCRIPT_DIR . DIRECTORY_SEPARATOR . $file
            );
        }
    }
}
