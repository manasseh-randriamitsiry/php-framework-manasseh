<?php

namespace App\Models;

use PDO;
use PDOException;

abstract class BaseModel
{
    protected $connection;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $guarded = [];
    protected $casts = [];
    protected $timestamps = true;
    
    public function __construct()
    {
        $this->connection = connect();
    }
    
    /**
     * Find a record by ID
     */
    public function find($id)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
            $statement = $this->connection->prepare($query);
            $statement->execute([$id]);
            
            $result = $statement->fetch(PDO::FETCH_OBJ);
            return $result ? $this->castAttributes($result) : null;
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'find');
        }
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null, $direction = 'ASC')
    {
        try {
            $query = "SELECT * FROM {$this->table}";
            
            if ($orderBy) {
                $query .= " ORDER BY {$orderBy} {$direction}";
            }
            
            $statement = $this->connection->prepare($query);
            $statement->execute();
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            return array_map([$this, 'castAttributes'], $results);
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'all');
        }
    }
    
    /**
     * Create a new record
     */
    public function create($data)
    {
        try {
            // Filter fillable fields
            $data = $this->filterFillable($data);
            
            // Validate data
            $this->validate($data);
            
            // Add timestamps
            if ($this->timestamps) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            
            $query = "INSERT INTO {$this->table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
            $statement = $this->connection->prepare($query);
            
            if ($statement->execute(array_values($data))) {
                return $this->find($this->connection->lastInsertId());
            }
            
            return false;
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'create');
        }
    }
    
    /**
     * Update a record
     */
    public function update($id, $data)
    {
        try {
            // Filter fillable fields
            $data = $this->filterFillable($data);
            
            // Validate data
            $this->validate($data);
            
            // Add updated timestamp
            if ($this->timestamps) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($data);
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $query = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
            $statement = $this->connection->prepare($query);
            
            $values = array_values($data);
            $values[] = $id;
            
            if ($statement->execute($values)) {
                return $this->find($id);
            }
            
            return false;
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'update');
        }
    }
    
    /**
     * Delete a record
     */
    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $statement = $this->connection->prepare($query);
            
            return $statement->execute([$id]);
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'delete');
        }
    }
    
    /**
     * Find records by criteria
     */
    public function where($column, $operator, $value = null)
    {
        try {
            // Handle where($column, $value) syntax
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            
            $query = "SELECT * FROM {$this->table} WHERE {$column} {$operator} ?";
            $statement = $this->connection->prepare($query);
            $statement->execute([$value]);
            
            $results = $statement->fetchAll(PDO::FETCH_OBJ);
            return array_map([$this, 'castAttributes'], $results);
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'where');
        }
    }
    
    /**
     * Count records
     */
    public function count($column = '*')
    {
        try {
            $query = "SELECT COUNT({$column}) as count FROM {$this->table}";
            $statement = $this->connection->prepare($query);
            $statement->execute();
            
            $result = $statement->fetch(PDO::FETCH_OBJ);
            return (int) $result->count;
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'count');
        }
    }
    
    /**
     * Execute raw query
     */
    public function query($sql, $params = [])
    {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($params);
            
            return $statement->fetchAll(PDO::FETCH_OBJ);
            
        } catch (PDOException $e) {
            $this->handleDatabaseError($e, 'query');
        }
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollback();
    }
    
    /**
     * Filter data to only fillable fields
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Cast attributes based on casts array
     */
    protected function castAttributes($object)
    {
        if (empty($this->casts)) {
            return $object;
        }
        
        foreach ($this->casts as $attribute => $cast) {
            if (isset($object->$attribute)) {
                switch ($cast) {
                    case 'int':
                    case 'integer':
                        $object->$attribute = (int) $object->$attribute;
                        break;
                    case 'float':
                    case 'double':
                        $object->$attribute = (float) $object->$attribute;
                        break;
                    case 'bool':
                    case 'boolean':
                        $object->$attribute = (bool) $object->$attribute;
                        break;
                    case 'array':
                    case 'json':
                        $object->$attribute = json_decode($object->$attribute, true);
                        break;
                }
            }
        }
        
        return $object;
    }
    
    /**
     * Validate data (override in child classes)
     */
    protected function validate($data)
    {
        // Override in child classes for specific validation
        return true;
    }
    
    /**
     * Handle database errors
     */
    protected function handleDatabaseError(PDOException $e, $operation)
    {
        // Log the error
        error_log("Database error in {$operation}: " . $e->getMessage());
        
        // Return user-friendly error based on error code
        switch ($e->getCode()) {
            case '23000':
                throw new \Exception("Data integrity violation. Please check for duplicate entries.");
            case '42S02':
                throw new \Exception("Table does not exist.");
            case '42S22':
                throw new \Exception("Column does not exist.");
            default:
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    throw new \Exception("Database error: " . $e->getMessage());
                } else {
                    throw new \Exception("A database error occurred. Please try again.");
                }
        }
    }
    
    /**
     * Get the table name
     */
    public function getTable()
    {
        return $this->table;
    }
    
    /**
     * Set the table name
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }
}