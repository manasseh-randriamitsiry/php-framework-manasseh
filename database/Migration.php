<?php

namespace Database;

use App\Logger;

class Migration
{
    protected static $migrationsTable = 'framework_migrations';
    protected static $connection = null;
    
    /**
     * Initialize migration system
     */
    public static function init()
    {
        if (self::$connection === null) {
            self::$connection = connect();
        }
        
        self::createMigrationsTable();
    }
    
    /**
     * Create migrations tracking table
     */
    protected static function createMigrationsTable()
    {
        try {
            $query = "
                CREATE TABLE IF NOT EXISTS " . self::$migrationsTable . " (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    batch INT NOT NULL,
                    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ";
            
            self::$connection->exec($query);
            
        } catch (\Exception $e) {
            Logger::error("Failed to create migrations table", [
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Could not create migrations table: " . $e->getMessage());
        }
    }
    
    /**
     * Run all pending migrations
     */
    public static function migrate()
    {
        self::init();
        
        try {
            $pendingMigrations = self::getPendingMigrations();
            
            if (empty($pendingMigrations)) {
                echo "No pending migrations.\n";
                return true;
            }
            
            $batch = self::getNextBatchNumber();
            $executed = 0;
            
            foreach ($pendingMigrations as $migration) {
                if (self::executeMigration($migration, $batch)) {
                    $executed++;
                    echo "Migrated: {$migration}\n";
                } else {
                    echo "Failed to migrate: {$migration}\n";
                    break;
                }
            }
            
            echo "Executed {$executed} migrations.\n";
            return $executed > 0;
            
        } catch (\Exception $e) {
            Logger::error("Migration failed", [
                'error' => $e->getMessage()
            ]);
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Rollback migrations
     */
    public static function rollback($steps = 1)
    {
        self::init();
        
        try {
            $lastBatch = self::getLastBatchNumber();
            
            if ($lastBatch === 0) {
                echo "Nothing to rollback.\n";
                return true;
            }
            
            $targetBatch = max(0, $lastBatch - $steps);
            $migrationsToRollback = self::getMigrationsToRollback($targetBatch);
            
            if (empty($migrationsToRollback)) {
                echo "No migrations to rollback.\n";
                return true;
            }
            
            $rolledBack = 0;
            
            // Rollback in reverse order
            foreach (array_reverse($migrationsToRollback) as $migration) {
                if (self::rollbackMigration($migration['migration'])) {
                    $rolledBack++;
                    echo "Rolled back: {$migration['migration']}\n";
                } else {
                    echo "Failed to rollback: {$migration['migration']}\n";
                    break;
                }
            }
            
            echo "Rolled back {$rolledBack} migrations.\n";
            return $rolledBack > 0;
            
        } catch (\Exception $e) {
            Logger::error("Rollback failed", [
                'error' => $e->getMessage()
            ]);
            echo "Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Get pending migrations
     */
    protected static function getPendingMigrations()
    {
        $migrationFiles = self::getMigrationFiles();
        $executedMigrations = self::getExecutedMigrations();
        
        return array_diff($migrationFiles, $executedMigrations);
    }
    
    /**
     * Get all migration files
     */
    protected static function getMigrationFiles()
    {
        $migrationPath = __DIR__ . '/migrations/';
        $files = glob($migrationPath . '*.php');
        $migrations = [];
        
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $migrations[] = $filename;
        }
        
        sort($migrations);
        return $migrations;
    }
    
    /**
     * Get executed migrations from database
     */
    protected static function getExecutedMigrations()
    {
        try {
            $query = "SELECT migration FROM " . self::$migrationsTable . " ORDER BY migration";
            $stmt = self::$connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
        } catch (\Exception $e) {
            Logger::error("Failed to get executed migrations", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Execute a single migration
     */
    protected static function executeMigration($migration, $batch)
    {
        try {
            $migrationPath = __DIR__ . '/migrations/' . $migration . '.php';
            
            if (!file_exists($migrationPath)) {
                throw new \Exception("Migration file not found: {$migrationPath}");
            }
            
            // Include migration file
            require_once $migrationPath;
            
            // Execute migration
            if (class_exists($migration)) {
                $migrationInstance = new $migration();
                if (method_exists($migrationInstance, 'up')) {
                    $migrationInstance->up();
                } else {
                    throw new \Exception("Migration {$migration} does not have an 'up' method");
                }
            } else {
                throw new \Exception("Migration class {$migration} not found");
            }
            
            // Record migration
            self::recordMigration($migration, $batch);
            
            Logger::info("Migration executed successfully", [
                'migration' => $migration,
                'batch' => $batch
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::error("Migration execution failed", [
                'migration' => $migration,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Rollback a single migration
     */
    protected static function rollbackMigration($migration)
    {
        try {
            $migrationPath = __DIR__ . '/migrations/' . $migration . '.php';
            
            if (!file_exists($migrationPath)) {
                throw new \Exception("Migration file not found: {$migrationPath}");
            }
            
            // Include migration file
            require_once $migrationPath;
            
            // Execute rollback
            if (class_exists($migration)) {
                $migrationInstance = new $migration();
                if (method_exists($migrationInstance, 'down')) {
                    $migrationInstance->down();
                } else {
                    throw new \Exception("Migration {$migration} does not have a 'down' method");
                }
            } else {
                throw new \Exception("Migration class {$migration} not found");
            }
            
            // Remove migration record
            self::removeMigrationRecord($migration);
            
            Logger::info("Migration rolled back successfully", [
                'migration' => $migration
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Logger::error("Migration rollback failed", [
                'migration' => $migration,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Record migration in database
     */
    protected static function recordMigration($migration, $batch)
    {
        $query = "INSERT INTO " . self::$migrationsTable . " (migration, batch) VALUES (?, ?)";
        $stmt = self::$connection->prepare($query);
        $stmt->execute([$migration, $batch]);
    }
    
    /**
     * Remove migration record from database
     */
    protected static function removeMigrationRecord($migration)
    {
        $query = "DELETE FROM " . self::$migrationsTable . " WHERE migration = ?";
        $stmt = self::$connection->prepare($query);
        $stmt->execute([$migration]);
    }
    
    /**
     * Get next batch number
     */
    protected static function getNextBatchNumber()
    {
        try {
            $query = "SELECT MAX(batch) as max_batch FROM " . self::$migrationsTable;
            $stmt = self::$connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return ($result['max_batch'] ?? 0) + 1;
            
        } catch (\Exception $e) {
            return 1;
        }
    }
    
    /**
     * Get last batch number
     */
    protected static function getLastBatchNumber()
    {
        try {
            $query = "SELECT MAX(batch) as max_batch FROM " . self::$migrationsTable;
            $stmt = self::$connection->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return $result['max_batch'] ?? 0;
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get migrations to rollback
     */
    protected static function getMigrationsToRollback($targetBatch)
    {
        try {
            $query = "SELECT migration FROM " . self::$migrationsTable . " WHERE batch > ? ORDER BY migration";
            $stmt = self::$connection->prepare($query);
            $stmt->execute([$targetBatch]);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            Logger::error("Failed to get migrations for rollback", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get migration status
     */
    public static function status()
    {
        self::init();
        
        $allMigrations = self::getMigrationFiles();
        $executedMigrations = self::getExecutedMigrations();
        
        echo "Migration Status:\n";
        echo str_repeat('-', 50) . "\n";
        
        foreach ($allMigrations as $migration) {
            $status = in_array($migration, $executedMigrations) ? 'Migrated' : 'Pending';
            echo sprintf("%-40s %s\n", $migration, $status);
        }
        
        $pendingCount = count(array_diff($allMigrations, $executedMigrations));
        echo str_repeat('-', 50) . "\n";
        echo "Total migrations: " . count($allMigrations) . "\n";
        echo "Executed: " . count($executedMigrations) . "\n";
        echo "Pending: " . $pendingCount . "\n";
    }
    
    /**
     * Create new migration file
     */
    public static function create($name)
    {
        $timestamp = date('Y_m_d_His');
        $className = $timestamp . '_' . $name;
        $filename = $className . '.php';
        $migrationPath = __DIR__ . '/migrations/' . $filename;
        
        $template = self::getMigrationTemplate($className);
        
        if (file_put_contents($migrationPath, $template) !== false) {
            echo "Migration created: {$filename}\n";
            return true;
        } else {
            echo "Failed to create migration: {$filename}\n";
            return false;
        }
    }
    
    /**
     * Get migration template
     */
    protected static function getMigrationTemplate($className)
    {
        return "<?php

class {$className}
{
    /**
     * Run the migration
     */
    public function up()
    {
        \$pdo = connect();
        
        try {
            \$query = \"
                CREATE TABLE IF NOT EXISTS example_table (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )
            \";
            
            \$pdo->exec(\$query);
            
        } catch (\\Exception \$e) {
            throw new \\Exception(\"Migration failed: \" . \$e->getMessage());
        }
    }
    
    /**
     * Reverse the migration
     */
    public function down()
    {
        \$pdo = connect();
        
        try {
            \$query = \"DROP TABLE IF EXISTS example_table\";
            \$pdo->exec(\$query);
            
        } catch (\\Exception \$e) {
            throw new \\Exception(\"Rollback failed: \" . \$e->getMessage());
        }
    }
}
";
    }
}