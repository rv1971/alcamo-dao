<?php

/**
 * @namespace alcamo\dao
 *
 * @brief Simple database access classes
 */

namespace alcamo\dao;

/**
 * @brief Wrapper for a PDO with some convenience
 *
 * @date last reviewed 2026-06-25
 */
class DbAccessor
{
    /// Attributes set in the constructor
    public const ATTRIBUTES = [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_STATEMENT_CLASS => [ Statement::class ],
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_CLASS
    ];

    /// Class to return when fetching records
    public const FETCH_CLASS = \StdClass::class;

    /// Create from parameters as in PDO::__construct()
    public static function newFromDsn(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null
    ): self {
        return new static(new \PDO($dsn, $username, $password, $options));
    }

    /**
     * @brief Create from named properties
     *
     * @param $props array|object properties with the names as the parameters
     * of alcamo::dao::DbAccessor::newFromDsn().
     */
    public static function newFromProps($props): self
    {
        $props = (object)$props;

        return static::newFromDsn(
            $props->dsn ?? null,
            $props->username ?? null,
            $props->password ?? null,
            $props->options ?? null
        );
    }

    private $pdo_;

    public function __construct(\PDO $pdo)
    {
        $this->pdo_ = $pdo;

        foreach (static::ATTRIBUTES as $attribute => $value) {
            $this->pdo_->setAttribute($attribute, $value);
        }
    }

    public function getPdo(): \PDO
    {
        return $this->pdo_;
    }

    /**
     * @brief Prepare an SQL statement
     *
     * @param $stmt SQL statement string
     *
     * @param $options See
     * [PDO::prepare()](https://www.php.net/manual/en/pdo.prepare) $options
     *
     * @param $fetchClass Name of class used to fetch records. [default
     * alcamo::dao::DbAccessor::FETCH_CLASS]
     */
    public function prepare(
        string $stmt,
        ?array $options = null,
        ?string $fetchClass = null
    ): \PDOStatement {
        $stmt = $this->pdo_->prepare($stmt, $options ?? []);

        /** Return fetched records as objects of class RECORD_CLASS. */
        $stmt->setFetchMode(
            \PDO::FETCH_CLASS,
            $fetchClass ?? static::FETCH_CLASS
        );

        return $stmt;
    }

    /// Execute a sequence of SQL statements as strings
    public function executeScript(iterable $stmts): void
    {
        foreach ($stmts as $stmt) {
            $this->prepare($stmt)->execute();
        }
    }
}
