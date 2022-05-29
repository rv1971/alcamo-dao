<?php

namespace alcamo\dao;

/**
 * @brief Table accessor with iterator over all table records
 */
class TableAccessor extends DbAccessor implements \Countable, \IteratorAggregate
{
    // SELECT statement for count()
    public const COUNT_STMT = 'SELECT COUNT(*) FROM %s';

    /// SELECT statement for getIterator()
    public const SELECT_STMT = 'SELECT * FROM %s ORDER BY 1, 2, 3 LIMIT 100';

    protected $tableName_;

    public function __construct($connection, string $tableName)
    {
        parent::__construct($connection);

        $this->tableName_ = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName_;
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
