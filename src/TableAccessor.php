<?php

namespace alcamo\dao;

/**
 * @brief Table accessor with iterator over all table records
 *
 * @todo Write unit tests
 *
 * @date Last reviewed 2021-06-14
 */
class TableAccessor extends AbstractDbAccessor implements \IteratorAggregate
{
    /// Default ORDER BY clause for iterator
    public const DEFAULT_ORDER_BY = '1, 2, 3';

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
        $stmt = $this->prepare(
            "SELECT * FROM $this->tableName_ ORDER BY "
            . static::DEFAULT_ORDER_BY
        );

        $stmt->execute();

        return $stmt;
    }
}
