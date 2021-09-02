<?php

/**
 * @namespace alcamo\dao
 *
 * @brief Simple database access classes
 *
 * @todo Write unit tests
 */

namespace alcamo\dao;

/**
 * @brief Wrapper for a PDO with some convenience
 */
class DbAccessor
{
    /// Class to return when fetching records
    public const RECORD_CLASS = \StdClass::class;

    protected $pdo_;

    /**
     * @param $connection One of:
     * - PDO object
     * - DbAccessor object
     * - associative array with required key `dsn` and optional keys
     * `username`, `password` and `options`
     * - numerically indexed array of $arguments for PDO::__construct
     * - DSN string
     */
    public function __construct($connection)
    {
        switch (true) {
            case $connection instanceof \PDO:
                $this->pdo_ = $connection;
                break;

            case $connection instanceof self:
                $this->pdo_ = $connection->pdo_;
                break;

            case is_array($connection) && isset($connection['dsn']):
                $this->pdo_ = new \PDO(
                    $connection['dsn'],
                    $connection['username'] ?? null,
                    $connection['password'] ?? null,
                    $connection['options'] ?? null
                );
                break;

            case is_array($connection):
                $this->pdo_ = new \PDO(...$connection);
                break;

            default:
                $this->pdo_ = new \PDO($connection);
        }

        /** Always throw exceptions on database errors */
        $this->pdo_->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @brief Prepare an SQL statement
     *
     * @param $stmt SQL statement string
     *
     * @param $options See
     * [PDO::prepare()](https://www.php.net/manual/en/pdo.prepare) $options
     */
    public function prepare(
        string $stmt,
        ?array $options = null
    ): \PDOStatement {
        $stmt = $this->pdo_->prepare($stmt, $options ?? []);

        /** Return fetched records as objects of class @ref RECORD_CLASS. */
        $stmt->setFetchMode(\PDO::FETCH_CLASS, static::RECORD_CLASS);

        return $stmt;
    }
}
