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

    /// Create from parameters as in PDO::__construct()
    public static function newFromDsn(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?array $options = null,
        ?string $tablePrefix = null
    ): self {
        return new static(
            new \PDO($dsn, $username, $password, $options),
            $tablePrefix
        );
    }

    /**
     * @brief Create from named properties
     *
     * @param $props array|object Properties with the names as the parameters
     * of alcamo::dao::DbAccessor::newFromDsn() plus optionally a
     * `tablePrefix` property.
     */
    public static function newFromProps($props): self
    {
        $props = (object)$props;

        return static::newFromDsn(
            $props->dsn ?? null,
            $props->username ?? null,
            $props->password ?? null,
            $props->options ?? null,
            $props->tablePrefix ?? null
        );
    }

    protected $pdo_;         ///< PDO object
    protected $tablePrefix_; ///< ?string

    /**
     * @param $pdo PDO object.
     *
     * @param $tablePrefix prefix to prepend to all table names. This allows
     * to access tables in a specific schema and/or to use a table naming
     * convention based on prefixes.
     */
    public function __construct(\PDO $pdo, ?string $tablePrefix = null)
    {
        $this->pdo_ = $pdo;

        foreach (static::ATTRIBUTES as $attribute => $value) {
            $this->pdo_->setAttribute($attribute, $value);
        }

        $this->tablePrefix_ = $tablePrefix;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo_;
    }

    public function getTablePrefix(): ?string
    {
        return $this->tablePrefix_;
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
    /// the table prefix, if any.
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
            str_replace('/*_*/', $this->tablePrefix_, $stmtSql),
            $options ?? []
        );

        /** Return fetched records as objects of class RECORD_CLASS. */
        $stmt->setFetchMode(
            \PDO::FETCH_CLASS,
            $fetchClass ?? static::FETCH_CLASS
        );

        return $stmt;
    }

    /// Execute a sequence of SQL statements as strings
    public function executeScript(iterable $stmtSqls): void
    {
        foreach ($stmtSqls as $stmtSql) {
            $stmt = $this->prepare($stmtSql);

            if (isset($stmt)) {
                $stmt->execute();
            }
        }
    }

    /**
     * @brief Execute an SQL file
     *
     * @warning The parser is extremely trivial and simply assumes that the
     * statements are exactly the chunks of text that terminate in semicolon
     * and linefeed.
     */
    public function executeSqlFile(string $pathname): void
    {
        $this->executeScript(explode(";\n", file_get_contents($pathname)));
    }
}
