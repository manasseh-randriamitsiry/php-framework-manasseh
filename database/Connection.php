<?php
namespace Database;
use PDO;
use PDOException;

class Connection
{
    protected static $instance;
    protected static $connections = [];
    protected $config;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Get singleton instance of connection manager
     */
    public static function getInstance($config)
    {
        if (!self::$instance) {
            self::$instance = new static($config);
        }
        return self::$instance;
    }
    
    /**
     * Create and return a database connection with improved error handling
     */
    public static function makeConnection($config)
    {
        try {
            // Validate configuration
            self::validateConfig($config);
            
            // Create connection string
            $dsn = self::buildDsn($config);
            
            // Create PDO instance with options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $connection = new PDO(
                $dsn,
                $config['user'],
                $config['password'],
                $options
            );
            
            // Set additional attributes
            $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            
            return $connection;
            
        } catch (PDOException $e) {
            self::handleConnectionError($e, $config);
        } catch (\Exception $e) {
            throw new \Exception("Database configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Get or create a named connection (connection pooling)
     */
    public static function getConnection($name = 'default', $config = null)
    {
        if (!isset(self::$connections[$name])) {
            if (!$config) {
                throw new \Exception("No configuration provided for connection '{$name}'");
            }
            
            self::$connections[$name] = self::makeConnection($config);
        }
        
        // Test connection and reconnect if needed
        self::testConnection(self::$connections[$name], $name, $config);
        
        return self::$connections[$name];
    }
    
    /**
     * Test if connection is still alive
     */
    protected static function testConnection($connection, $name, $config)
    {
        try {
            $connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Connection lost, try to reconnect
            if ($config) {
                self::$connections[$name] = self::makeConnection($config);
            }
        }
    }
    
    /**
     * Build DSN string from configuration
     */
    protected static function buildDsn($config)
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'];
        $port = $config['port'] ?? 3306;
        $dbname = $config['dbname'];
        $charset = $config['charset'] ?? 'utf8mb4';
        
        switch ($driver) {
            case 'mysql':
                return "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
            case 'pgsql':
                return "pgsql:host={$host};port={$port};dbname={$dbname}";
            case 'sqlite':
                return "sqlite:{$dbname}";
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }
    }
    
    /**
     * Validate database configuration
     */
    protected static function validateConfig($config)
    {
        $required = ['host', 'dbname', 'user'];
        
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \Exception("Missing required database configuration: {$key}");
            }
        }
        
        // Validate host format
        if (!filter_var($config['host'], FILTER_VALIDATE_IP) && !filter_var($config['host'], FILTER_VALIDATE_DOMAIN)) {
            if ($config['host'] !== 'localhost') {
                throw new \Exception("Invalid database host format: {$config['host']}");
            }
        }
        
        // Validate port if provided
        if (isset($config['port']) && (!is_numeric($config['port']) || $config['port'] < 1 || $config['port'] > 65535)) {
            throw new \Exception("Invalid database port: {$config['port']}");
        }
    }
    
    /**
     * Handle connection errors with detailed logging
     */
    protected static function handleConnectionError(PDOException $e, $config)
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();
        
        // Log error (in a real application, use proper logging)
        error_log("Database connection failed: {$errorMessage}");
        
        // Provide user-friendly error messages
        switch ($errorCode) {
            case 1045:
                throw new \Exception("Database authentication failed. Please check username and password.");
            case 1049:
                throw new \Exception("Database '{$config['dbname']}' does not exist.");
            case 2002:
                throw new \Exception("Cannot connect to database server at '{$config['host']}'. Server may be down.");
            case 2005:
                throw new \Exception("Unknown database host '{$config['host']}'.");
            default:
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    throw new \Exception("Database connection error: {$errorMessage}");
                } else {
                    throw new \Exception("Database connection failed. Please contact administrator.");
                }
        }
    }
    
    /**
     * Close a specific connection
     */
    public static function closeConnection($name = 'default')
    {
        if (isset(self::$connections[$name])) {
            self::$connections[$name] = null;
            unset(self::$connections[$name]);
        }
    }
    
    /**
     * Close all connections
     */
    public static function closeAllConnections()
    {
        foreach (self::$connections as $name => $connection) {
            self::closeConnection($name);
        }
    }
    
    /**
     * Get connection statistics
     */
    public static function getConnectionStats()
    {
        return [
            'active_connections' => count(self::$connections),
            'connection_names' => array_keys(self::$connections)
        ];
    }
    
    /**
     * Execute a query with automatic retry on connection failure
     */
    public static function executeWithRetry($connection, $query, $params = [], $maxRetries = 3)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $stmt = $connection->prepare($query);
                $stmt->execute($params);
                return $stmt;
            } catch (PDOException $e) {
                $attempt++;
                
                // Check if it's a connection error
                if (in_array($e->getCode(), [2006, 2013]) && $attempt < $maxRetries) {
                    // Connection lost, wait and retry
                    sleep(1);
                    continue;
                }
                
                throw $e;
            }
        }
        
        throw new \Exception("Query failed after {$maxRetries} attempts");
    }
}
