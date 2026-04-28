<?php

//   Database Singleton manages PDO connection with retry logic and error handling
class Database {
    private static $instance = null;
    private $connection;
    private $lastError;
    private $retryCount = 0;
    private $maxRetries = 3;

//private constructor to prevent direct instantiation
    private function __construct() {
        $this->connect();
    }

    /** 
     * Get singleton instance
     * @return Database
     */
  
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //Establish database  connection with retry logic
    private function connect() {
        $config = [
            'host' => getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost'),
            'port' => getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : '3306'),
            'dbname' => getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'AutoSpares'),
            'username' => getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root'),
            'password' => getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : ''),
        ];

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";

        while ($this->retryCount < $this->maxRetries) {
            try {
                $this->connection = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => false,
                    ]
                );
                $this->retryCount = 0;
                return;
            } catch (PDOException $e) {
                $this->lastError = $e->getMessage();
                $this->retryCount++;
                if ($this->retryCount >= $this->maxRetries) {
                    throw $e;
                }
                usleep(100000);// 100ms delay before retrying
            }
        }
    }

    /**
     * Get PDO connection 
     * @return PDO
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * Test connection 
     * @return bool
     */
    public function testConnection() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get last error
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

//disconnect db connection
    public function disconnect() {
        $this->connection = null;
    }

//prevent cloning of singleton
    private function __clone() {}

 //prevent unserialization of singleton
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
