<?php

namespace App\Models;

/**
 * Base Model
 * Provides common ORM functionality to all models
 * Different approach than electronics backend
 */
abstract class Model {
    protected $conn;
    protected $table;
    protected $fillable = [];
    protected $hidden = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->conn = \Database::getInstance()->getConnection();
    }

    /**
     * Execute raw SQL query
     * @param string $sql
     * @param array $bindings
     * @return PDOStatement
     */
    protected function query($sql, $bindings = []) {
        $stmt = $this->conn->prepare($sql);
        foreach ($bindings as $key => $value) {
            $param = is_string($key) ? $key : $key + 1;
            $type = \PDO::PARAM_STR;

            if (is_int($value)) {
                $type = \PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = \PDO::PARAM_NULL;
            }

            $stmt->bindValue($param, $value, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * Get single row
     * @param string $sql
     * @param array $bindings
     * @return array|null
     */
    protected function getRow($sql, $bindings = []) {
        $result = $this->query($sql, $bindings)->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get multiple rows
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    protected function getRows($sql, $bindings = []) {
        return $this->query($sql, $bindings)->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get single value
     * @param string $sql
     * @param array $bindings
     * @return mixed
     */
    protected function getValue($sql, $bindings = []) {
        $row = $this->getRow($sql, $bindings);
        if ($row) {
            return array_values($row)[0] ?? null;
        }
        return null;
    }

    /**
     * Insert record
     * @param array $data
     * @return int|bool Last insert ID or false
     */
    protected function insert($data) {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => '?', $columns);
        $sql = "INSERT INTO {$this->table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        return $this->conn->lastInsertId();
    }

    /**
     * Update records
     * @param array $data
     * @param string $whereClause
     * @param array $whereValues
     * @return int Affected rows
     */
    protected function update($data, $whereClause, $whereValues = []) {
        $setClause = implode(', ', array_map(fn($col) => "$col = ?", array_keys($data)));
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$whereClause}";
        
        $bindings = array_merge(array_values($data), $whereValues);
        return $this->query($sql, $bindings)->rowCount();
    }

    /**
     * Delete records
     * @param string $whereClause
     * @param array $whereValues
     * @return int Deleted rows
     */
    protected function delete($whereClause, $whereValues = []) {
        $sql = "DELETE FROM {$this->table} WHERE {$whereClause}";
        return $this->query($sql, $whereValues)->rowCount();
    }

    /**
     * Count records
     * @param string $whereClause
     * @param array $whereValues
     * @return int
     */
    protected function count($whereClause = '1=1', $whereValues = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereClause}";
        return (int) $this->getValue($sql, $whereValues);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollBack();
    }

    /**
     * Get table name
     * @return string
     */
    public function getTableName() {
        return $this->table;
    }
}
