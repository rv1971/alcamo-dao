<?php

namespace alcamo\dao;

/**
 * @brief Statement class with some simple enhancements
 */
class Statement extends \PDOStatement
{
    /// Return $this to allow for chaining
    public function executeAndReturnSelf(?array $params = null): self
    {
        // No need to check the return value, thanks to PDO::ERRMODE_EXCEPTION
        parent::execute($params);

        return $this;
    }
}
