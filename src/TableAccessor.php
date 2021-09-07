<?php

namespace alcamo\dao;

/**
 * @brief Table accessor with iterator over all table records
 */
class TableAccessor extends DbAccessor implements \IteratorAggregate
{
    /// SELECT statement for iterator
    public const SELECT_STMT = 'SELECT * FROM %s ORDER BY 1, 2, 3';

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

    /// Return iterator over all table records
    public function getIterator(): \Traversable
    {
        return $this
            ->prepare(sprintf(static::SELECT_STMT, $this->tableName_))
            ->executeAndReturnSelf();
    }
}
