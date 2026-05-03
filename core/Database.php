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
        $databaseUrl = getenv('DATABASE_URL');

        if (empty($databaseUrl)) {
            $configuredHost = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '');
            if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $configuredHost)) {
                $databaseUrl = $configuredHost;
            }
        }

        if (!empty($databaseUrl)) {
            $parts = parse_url($databaseUrl);
            $scheme = $parts['scheme'] ?? '';
            $host = $parts['host'] ?? 'localhost';
            $isPostgres = in_array($scheme, ['pgsql', 'postgres', 'postgresql'], true);
            $port = $parts['port'] ?? ($isPostgres ? 5432 : 3306);
            $username = $parts['user'] ?? '';
            $password = $parts['pass'] ?? '';
            $dbname = isset($parts['path']) ? ltrim($parts['path'], '/') : '';

            if ($isPostgres) {
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
            } else {
                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            }
        } else {
            $config = [
                'host' => getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : 'localhost'),
                'port' => getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : '3306'),
                'dbname' => getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'AutoSpares'),
                'username' => getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root'),
                'password' => getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : ''),
            ];

            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            $username = $config['username'];
            $password = $config['password'];
        }

        while ($this->retryCount < $this->maxRetries) {
            try {
                $this->connection = new PDO(
                    $dsn,
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_PERSISTENT => false,
                    ]
                );
                $this->retryCount = 0;
                $this->initializeSchema();
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

    private function initializeSchema() {
        if (getenv('AUTO_INIT_DB') === 'false') {
            return;
        }

        $driver = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $this->initializePostgresSchema();
            return;
        }

        if ($driver === 'mysql') {
            $this->initializeMysqlSchema();
        }
    }

    private function initializePostgresSchema() {
        $statements = [
            "CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                firstname VARCHAR(50) NOT NULL,
                lastname VARCHAR(50) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'Customer',
                email VARCHAR(100) NOT NULL UNIQUE,
                phonenumber VARCHAR(15),
                address VARCHAR(255),
                city VARCHAR(50),
                country VARCHAR(50),
                password VARCHAR(255) NOT NULL,
                createdat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updatedat TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS categories (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS sub_categories (
                id SERIAL PRIMARY KEY,
                category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
                name VARCHAR(50) NOT NULL UNIQUE,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS brands (
                id SERIAL PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS models (
                id SERIAL PRIMARY KEY,
                brand_id INT NOT NULL REFERENCES brands(id) ON DELETE CASCADE,
                name VARCHAR(50) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS products (
                id SERIAL PRIMARY KEY,
                sub_category_id INT NOT NULL REFERENCES sub_categories(id) ON DELETE CASCADE,
                model_id INT NOT NULL REFERENCES models(id) ON DELETE CASCADE,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price NUMERIC(10, 2) NOT NULL,
                stock_quantity INT NOT NULL,
                img VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS orders (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                customer_name VARCHAR(100) NOT NULL,
                customer_phone_number VARCHAR(15) NOT NULL,
                customer_address VARCHAR(255) NOT NULL,
                total_amount NUMERIC(10, 2) NOT NULL,
                status VARCHAR(20) DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS order_items (
                id SERIAL PRIMARY KEY,
                order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                product_id INT NOT NULL REFERENCES products(id) ON DELETE CASCADE,
                quantity INT NOT NULL,
                price NUMERIC(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS payments (
                id SERIAL PRIMARY KEY,
                order_id INT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
                payment_method VARCHAR(50) NOT NULL,
                payment_status VARCHAR(20) DEFAULT 'Pending',
                merchant_reference VARCHAR(100),
                paynow_reference VARCHAR(100),
                poll_url VARCHAR(255),
                browser_url VARCHAR(255),
                payment_details TEXT,
                paid_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
        ];

        foreach ($statements as $statement) {
            $this->connection->exec($statement);
        }

        $this->seedPostgresData();
    }

    private function initializeMysqlSchema() {
        $schemaFile = __DIR__ . '/../sql/schema.sql';
        if (is_file($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
            $sql = preg_replace('/USE\s+[^;]+;/i', '', $sql);
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                $this->connection->exec($statement);
            }
        }
    }

    private function seedPostgresData() {
        $this->connection->beginTransaction();

        try {
            $adminPassword = password_hash('123456', PASSWORD_BCRYPT, ['cost' => defined('BCRYPT_COST') ? BCRYPT_COST : 10]);
            $this->connection
                ->prepare("INSERT INTO users (firstname, lastname, role, email, password, createdat, updatedat)
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                    ON CONFLICT (email) DO UPDATE SET
                        firstname = EXCLUDED.firstname,
                        lastname = EXCLUDED.lastname,
                        role = EXCLUDED.role,
                        password = EXCLUDED.password,
                        updatedat = CURRENT_TIMESTAMP")
                ->execute(['Admin', 'User', 'Admin', 'stilesmvura@gmail.com', $adminPassword]);

            $categories = [
                ['Engine Parts', 'Replacement parts for engine repair and maintenance.'],
                ['Brake System', 'Pads, discs, hydraulics, and brake service parts.'],
                ['Electrical', 'Batteries, lighting, sensors, and electrical spares.'],
                ['Suspension', 'Shocks, struts, bushings, and steering components.'],
            ];

            foreach ($categories as [$name, $description]) {
                $this->connection
                    ->prepare("INSERT INTO categories (name, description) VALUES (?, ?) ON CONFLICT (name) DO NOTHING")
                    ->execute([$name, $description]);
            }

            $subCategories = [
                ['Engine Parts', 'Filters', 'Oil, air, and fuel filters.'],
                ['Engine Parts', 'Cooling', 'Radiators, thermostats, and water pumps.'],
                ['Brake System', 'Brake Pads', 'Front and rear brake pad sets.'],
                ['Electrical', 'Batteries', 'Vehicle batteries and terminals.'],
                ['Suspension', 'Shock Absorbers', 'Front and rear shock absorbers.'],
            ];

            foreach ($subCategories as [$categoryName, $name, $description]) {
                $categoryId = $this->fetchPostgresId('categories', $categoryName);
                if ($categoryId) {
                    $this->connection
                        ->prepare("INSERT INTO sub_categories (category_id, name, description) VALUES (?, ?, ?) ON CONFLICT (name) DO NOTHING")
                        ->execute([$categoryId, $name, $description]);
                }
            }

            foreach (['Toyota', 'Honda', 'Nissan', 'Ford'] as $brand) {
                $this->connection
                    ->prepare("INSERT INTO brands (name) VALUES (?) ON CONFLICT (name) DO NOTHING")
                    ->execute([$brand]);
            }

            $models = [
                ['Toyota', 'Corolla'],
                ['Toyota', 'Hilux'],
                ['Honda', 'Fit'],
                ['Nissan', 'Navara'],
                ['Ford', 'Ranger'],
            ];

            foreach ($models as [$brandName, $name]) {
                $brandId = $this->fetchPostgresId('brands', $brandName);
                if ($brandId) {
                    $this->connection
                        ->prepare("INSERT INTO models (brand_id, name) VALUES (?, ?) ON CONFLICT (name) DO NOTHING")
                        ->execute([$brandId, $name]);
                }
            }

            $products = [
                ['Filters', 'Corolla', 'Premium Oil Filter', 'High-flow oil filter for everyday service intervals.', 12.99, 40, '/uploads/product-11-67b990ddf73e3f51.png'],
                ['Brake Pads', 'Corolla', 'Ceramic Brake Pad Set', 'Low-dust ceramic pads for reliable stopping power.', 49.99, 18, '/uploads/product-11-67b990ddf73e3f51.png'],
                ['Cooling', 'Hilux', 'Radiator Assembly', 'Durable radiator assembly for diesel and petrol models.', 149.99, 7, '/uploads/product-11-67b990ddf73e3f51.png'],
                ['Batteries', 'Fit', 'Maintenance Free Battery', 'Long-life 12V battery for compact vehicles.', 89.99, 12, '/uploads/product-11-67b990ddf73e3f51.png'],
                ['Shock Absorbers', 'Ranger', 'Heavy Duty Shock Absorber', 'Stable ride control for work and off-road use.', 74.99, 15, '/uploads/product-11-67b990ddf73e3f51.png'],
                ['Filters', 'Navara', 'Air Filter Element', 'Replacement air filter for cleaner engine intake.', 18.99, 25, '/uploads/product-11-67b990ddf73e3f51.png'],
            ];

            foreach ($products as [$subCategoryName, $modelName, $name, $description, $price, $stockQuantity, $image]) {
                $subCategoryId = $this->fetchPostgresId('sub_categories', $subCategoryName);
                $modelId = $this->fetchPostgresId('models', $modelName);

                if ($subCategoryId && $modelId) {
                    $this->connection
                        ->prepare("INSERT INTO products (sub_category_id, model_id, name, description, price, stock_quantity, img)
                            SELECT ?, ?, ?, ?, ?, ?, ?
                            WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = ?)")
                        ->execute([$subCategoryId, $modelId, $name, $description, $price, $stockQuantity, $image, $name]);
                }
            }

            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    private function fetchPostgresId($table, $name) {
        $stmt = $this->connection->prepare("SELECT id FROM {$table} WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
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
