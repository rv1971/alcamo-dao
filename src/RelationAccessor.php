<?php

namespace alcamo\dao;

/**
 * @brief Relation accessor with iterator over all relation records
 *
 * @warning No sanitization takes place on method arguments. The caller must
 * have done sanitization before, if necessary.
 *
 * @date last reviewed 2026-06-25
 */
class RelationAccessor implements \Countable, \IteratorAggregate
{
    /// Relation name when not explicitely given to the constructor
    public const RELATION_NAME = null;

    /// Class to return when fetching records
    public const FETCH_CLASS = \StdClass::class;

    // SELECT statement for count()
    public const COUNT_STMT = 'SELECT COUNT(*) FROM /*_*/%s';

    /// SELECT statement for getIterator()
    public const SELECT_STMT =
        'SELECT * FROM /*_*/%s ORDER BY 1, 2, 3 LIMIT 100';

    /**
     * @brief Create from named properties
     *
     * @param $props array|object Properties with the names as the parameters
     * of alcamo::dao::DbAccessor::newFromDsn() plus a `relationName` property.
     */
    public static function newFromProps($props): self
    {
        $props = (object)$props;

        return new static(
            DbAccessor::newFromProps($props),
            $props->relationName ?? null
        );
    }

    protected $dbAccessor_;
    protected $relationName_;

    public function __construct(
        DbAccessor $dbAccessor,
        ?string $relationName = null
    ) {
        $this->dbAccessor_ = $dbAccessor;
        $this->relationName_ = $relationName ?? static::RELATION_NAME;
    }

    public function getDbAccessor(): DbAccessor
    {
        return $this->dbAccessor_;
    }

    public function getRelationName(): string
    {
        return $this->relationName_;
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

    /// Execute $querySql with parameters $params
    public function query(string $querySql, ?array $params = null): \Traversable
    {
        return $this->prepare($this->dbAccessor_->replaceNamePrefix($querySql))
            ->executeAndReturnSelf($params);
    }

    // Count all records
    public function count(): int
    {
        return $this
            ->query(sprintf(static::COUNT_STMT, $this->relationName_))
            ->fetchColumn();
    }

    /// Use query() to iterate over all records
    public function getIterator(): \Traversable
    {
        return $this->query(sprintf(static::SELECT_STMT, $this->relationName_));
    }
}
