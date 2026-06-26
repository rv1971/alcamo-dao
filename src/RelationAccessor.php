<?php

namespace alcamo\dao;

/**
 * @brief Relation accessor with iterator over all relation records
 *
 * Statements in the class constant STMT_MAP are available via getStmt(),
 * caching the prepared statements for reuse. Derived classes may add any
 * number of entries to STMT_MAP as needed.
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

    /// Map of statement IDs to SQL statements with optional options
    public const STMT_MAP = [
        'count'  => [ 'SELECT COUNT(*) FROM /*_*/%s' ],
        'select' => [ 'SELECT * FROM /*_*/%s ORDER BY 1, 2, 3 LIMIT 100' ]
    ];

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

    private $stmtCache_ = []; ///< Map of string IDs to prepared statements

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
     * @param $stmtSql SQL statement string
     *
     * @param $options See
     * [PDO::prepare()](https://www.php.net/manual/en/pdo.prepare) $options
     */
    public function prepare(
        string $stmtSql,
        ?array $options = null
    ): \PDOStatement {
        $stmt = $this->dbAccessor_
            ->prepare($stmtSql, $options, static::FETCH_CLASS);

        return $stmt;
    }

    /**
     * @brief Get a prepared statement from the cache
     *
     * @param $id Key in alcamo_dao::RelationAccessor::STMT_MAP.
     */
    public function getStmt(string $id): \PDOStatement
    {
        return $this->stmtCache_[$id]
        ?? (
            $this->stmtCache_[$id] =
                $this->prepare(
                    sprintf(static::STMT_MAP[$id][0], $this->relationName_),
                    static::STMT_MAP[$id][1] ?? null
                )
        );
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
        return $this->getStmt('count')->executeAndReturnSelf()->fetchColumn();
    }

    /// Iterate over all records
    public function getIterator(): \Traversable
    {
        return $this->getStmt('select')->executeAndReturnSelf();
    }
}
