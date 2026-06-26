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
 * @warning No sanitization takes place on method arguments. The caller must
 * have done sanitization before, if necessary.
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

    /// Statement to test whether a relation exists
    protected const RELATION_EXISTS_STMT = 'SELECT 1 from /*_*/%s LIMIT 1';

    /// Create from parameters as in PDO::__construct()
    public static function newFromDsn(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        ?string $namePrefix = null
    ): self {
        return new static(
            new \PDO($dsn, $username, $password, $options),
            $namePrefix
        );
    }

    /**
     * @brief Create from named properties
     *
     * @param $props array|object Properties with the names as the parameters
     * of alcamo::dao::DbAccessor::newFromDsn() plus optionally a
     * `namePrefix` property.
     */
    public static function newFromProps($props): self
    {
        $props = (object)$props;

        return static::newFromDsn(
            $props->dsn ?? null,
            $props->username ?? null,
            $props->password ?? null,
            $props->options ?? null,
            $props->namePrefix ?? null
        );
    }

    protected $pdo_;         ///< PDO object
    protected $namePrefix_; ///< ?string

    /**
     * @param $pdo PDO object.
     *
     * @param $namePrefix prefix to prepend to all relation names. This allows
     * to access relations in a specific schema and/or to use a relation naming
     * convention based on prefixes.
     */
    public function __construct(\PDO $pdo, ?string $namePrefix = null)
    {
        $this->pdo_ = $pdo;

        foreach (static::ATTRIBUTES as $attribute => $value) {
            $this->pdo_->setAttribute($attribute, $value);
        }

        $this->namePrefix_ = $namePrefix;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo_;
    }

    public function getNamePrefix(): ?string
    {
        return $this->namePrefix_;
    }

    public function getDriverName(): string
    {
        return $this->pdo_->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    ///
    /// @brief Replace all occurrences of `/*_*/` in $sql *verbatim* by the
    /// name prefix, if any.
    ///
    public function replaceNamePrefix(string $sql): string
    {
        return str_replace('/*_*/', $this->namePrefix_, $sql);
    }

    /**
     * @brief Prepare an SQL statement
     *
     * @param $stmtSql SQL statement string
     *
     * @param $options See
     * [PDO::prepare()](https://www.php.net/manual/en/pdo.prepare) $options
     *
     * @param $fetchClass Name of class used to fetch records. [default
     * alcamo::dao::DbAccessor::FETCH_CLASS]
     */
    /// @note All occurrences of `/*_*/` in $stmtSql are *verbatim* replaced by
    /// the relation prefix, if any.
    public function prepare(
        string $stmtSql,
        ?array $options = null,
        ?string $fetchClass = null
    ): ?\PDOStatement {
        /** Any comments starting with `--` at the beginning of a line are
         *  removed. If the result is an empty string, return null. A
         *  semicolon at the end of a statement, if any, is removed. */
        $stmtSql = rtrim(
            trim(preg_replace('/\n--[^\n]*\n/', "\n", "\n$stmtSql\n")),
            ';'
        );

        if ($stmtSql == '') {
            return null;
        }

        $stmt = $this->pdo_->prepare(
            $this->replaceNamePrefix($stmtSql),
            $options ?? []
        );

        /** Return fetched records as objects of class RECORD_CLASS. */
        $stmt->setFetchMode(
            \PDO::FETCH_CLASS,
            $fetchClass ?? static::FETCH_CLASS
        );

        return $stmt;
    }

    /**
     * @brief Execute a sequence of SQL statements as strings
     *
     * @param $stmtSqls string|array Statements to execute.
     *
     * @warning The parser for string $stmtSqls is extremely trivial and
     * simply assumes that the statements are exactly the chunks of text that
     * terminate in semicolon and linefeed.
     */
    public function executeScript($stmtSqls): void
    {
        if (!is_iterable($stmtSqls)) {
            $stmtSqls = explode(";\n", $stmtSqls);
        }

        foreach ($stmtSqls as $stmtSql) {
            $stmt = $this->prepare($stmtSql);

            if (isset($stmt)) {
                $stmt->execute();
            }
        }
    }

    /**
     * @brief Execute an SQL file
     */
    public function executeSqlFile(string $pathname): void
    {
        $this->executeScript(file_get_contents($pathname));
    }

    /**
     * @brief Whether a relation (table or view) $relationName exists
     *
     * @param $relationName Name of the relation *without prefix*.
     */
    public function relationExists(string $relationName): bool
    {
        try {
            $this->pdo_->query(
                $this->replaceNamePrefix(
                    sprintf(static::RELATION_EXISTS_STMT, $relationName)
                )
            );
        } catch (\PDOException $e) {
            if ($e->getCode() == 'HY000') {
                return false;
            } else {
                throw $e;
            }
        }

        return true;
    }
}
