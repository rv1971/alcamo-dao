<?php

namespace alcamo\dao;

/**
 * @brief Table accessor with iterator over all table records
 *
 * @warning No sanitization takes place on method arguments. The caller must
 * have done sanitization before, if necessary.
 *
 * @date last reviewed 2026-06-25
 */
class TableAccessor implements \Countable, \IteratorAggregate
{
    /// Class to return when fetching records
    public const FETCH_CLASS = \StdClass::class;

    // SELECT statement for count()
    public const COUNT_STMT = 'SELECT COUNT(*) FROM %s';

    /// SELECT statement for getIterator()
    public const SELECT_STMT = 'SELECT * FROM %s ORDER BY 1, 2, 3 LIMIT 100';

    /**
     * @brief Create from named properties
     *
     * @param $props array|object Properties with the names as the parameters
     * of alcamo::dao::DbAccessor::newFromDsn() plus a `tableName` property.
     */
    public static function newFromProps($props): self
    {
        $props = (object)$props;

        return new static(DbAccessor::newFromProps($props), $props->tableName);
    }

    protected $dbAccessor_;
    protected $tableName_;

    public function __construct(DbAccessor $dbAccessor, string $tableName)
    {
        $this->dbAccessor_ = $dbAccessor;
        $this->tableName_ = $tableName;
    }

    public function getDbAccessor(): DbAccessor
    {
        return $this->dbAccessor_;
    }

    public function getTableName(): string
    {
        return $this->tableName_;
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
        $stmt = $this->dbAccessor_
            ->prepare($stmt, $options, static::FETCH_CLASS);

        return $stmt;
    }

    /// Execute $sql with parameters $params
    public function query(string $sql, ?array $params = null): \Traversable
    {
        return $this->prepare($sql)->executeAndReturnSelf($params);
    }

    // Count all records
    public function count(): int
    {
        return $this
            ->query(sprintf(static::COUNT_STMT, $this->tableName_))
            ->fetchColumn();
    }

    /// Use query() to iterate over all records
    public function getIterator(): \Traversable
    {
        return $this->query(sprintf(static::SELECT_STMT, $this->tableName_));
    }
}
