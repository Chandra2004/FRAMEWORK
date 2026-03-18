<?php

namespace TheFramework\App\Database;

use PDO;
use PDOException;
use TheFramework\App\Database\DatabaseException;
use TheFramework\App\Core\Config;

class Database
{
    private static array $instances = [];
    private string $connectionName;
    private ?PDO $dbh = null;
    private $stmt;
    private bool $isConnected = false;
    private static bool $databaseEnabled = true;
    private array $queryLog = [];

    private function __construct(string $name = 'default')
    {
        $this->connectionName = $name;
    }

    /**
     * Set apakah database enabled atau tidak
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$databaseEnabled = $enabled;
    }

    /**
     * Check apakah database enabled
     */
    public static function isEnabled(): bool
    {
        return self::$databaseEnabled;
    }

    /**
     * Check apakah database sudah terhubung
     */
    public function isConnected(): bool
    {
        return $this->isConnected && $this->dbh !== null;
    }

    /**
     * Test koneksi database (mencoba connect dan return status)
     * Method ini akan mencoba membuat koneksi jika belum ada
     * 
     * @return bool True jika berhasil connect, false jika gagal
     */
    public function testConnection(): bool
    {
        // Jika sudah connected, return true
        if ($this->isConnected()) {
            return true;
        }

        // Jika database disabled, return false
        if (!self::$databaseEnabled) {
            return false;
        }

        // Coba connect, tapi jangan throw exception
        try {
            // Gunakan ensureConnection(true) untuk benar-benar mencoba connect
            $this->ensureConnection(true);
            return $this->isConnected();
        } catch (\Exception $e) {
            // Jika ada error, return false tanpa throw
            // Reset state jika connection gagal
            $this->isConnected = false;
            $this->dbh = null;
            return false;
        }
    }

    /**
     * Dapatkan koneksi berdasarkan nama.
     *
     * @param string $name
     * @return Database
     */
    public static function connection(string $name = 'default'): self
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }
        return self::$instances[$name];
    }

    /**
     * Mendapatkan instance default (singleton backward compatibility)
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        return static::connection('default');
    }

    /**
     * Ensure database connection (lazy connection)
     * 
     * @param bool $required Jika true, throw exception jika tidak bisa connect
     * @throws DatabaseException
     */
    public function ensureConnection(bool $required = false): void
    {
        if ($this->isConnected()) {
            return;
        }

        if (!self::$databaseEnabled) {
            if ($required) {
                throw new DatabaseException(
                    "Database connection is required but database is disabled. Please enable database in your configuration.",
                    500,
                    null,
                    [],
                    [],
                    true
                );
            }
            return;
        }

        try {
            $this->connect();
        } catch (DatabaseException $e) {
            if ($required) {
                // Create new exception with connection required flag
                throw new DatabaseException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e->getPrevious(),
                    $e->getConfigErrors(),
                    $e->getEnvErrors(),
                    true
                );
            }
            throw $e;
        }
    }

    /**
     * Buat koneksi PDO
     *
     * Menangani perubahan konstanta ATTR_INIT_COMMAND (PHP 8.5+)
     * 
     * @throws DatabaseException
     */
    private function connect(): void
    {
        $configErrors = [];
        $envErrors = [];

        // Pastikan Config::loadEnv() dan Config::get() tersedia pada aplikasi Anda.
        if (!class_exists('\\TheFramework\\App\\Core\\Config')) {
            throw new DatabaseException(
                "Config class tidak ditemukan. Pastikan \\TheFramework\\App\\Core\\Config tersedia.",
                500,
                null,
                ['Config class tidak ditemukan'],
                []
            );
        }

        Config::loadEnv();

        $prefix = $this->connectionName === 'default' ? '' : strtoupper($this->connectionName) . '_';

        // Get database configuration
        $host = Config::get($prefix . 'DB_HOST') ?: Config::get($prefix . 'MYSQLHOST');
        $dbname = Config::get($prefix . 'DB_NAME') ?: Config::get($prefix . 'MYSQLDATABASE');
        $user = Config::get($prefix . 'DB_USER') ?: Config::get($prefix . 'MYSQLUSER');
        $pass = Config::get($prefix . 'DB_PASS') ?: Config::get($prefix . 'MYSQLPASSWORD');
        $port = Config::get($prefix . 'DB_PORT') ?: Config::get($prefix . 'MYSQLPORT', '3306');
        $debug = Config::get('APP_DEBUG', false);

        // Fallback ke default jika koneksi bernama tidak ditemukan env-nya
        if ($this->connectionName !== 'default' && empty($host)) {
            $host = Config::get('DB_HOST') ?: Config::get('MYSQLHOST');
            $dbname = Config::get('DB_NAME') ?: Config::get('MYSQLDATABASE');
            $user = Config::get('DB_USER') ?: Config::get('MYSQLUSER');
            $pass = Config::get('DB_PASS') ?: Config::get('MYSQLPASSWORD');
            $port = Config::get('DB_PORT') ?: Config::get('MYSQLPORT', '3306');
        }

        // Check for missing required values (after considering fallbacks)
        if (empty($host)) {
            $configErrors[] = "Database Host (DB_HOST/MYSQLHOST) is missing or empty";
        }
        if (empty($dbname)) {
            $configErrors[] = "Database Name (DB_NAME/MYSQLDATABASE) is missing or empty";
        }
        if (empty($user)) {
            $configErrors[] = "Database User (DB_USER/MYSQLUSER) is missing or empty";
        }

        // Only check for typos if .env file exists (don't error if it's missing in production)
        $root = defined('ROOT_DIR') ? ROOT_DIR : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3));
        $envFile = $root . '/.env';
        if (file_exists($envFile)) {
            $envContent = file_get_contents($envFile);
            $expectedVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
            $foundVars = [];

            foreach ($expectedVars as $var) {
                if (preg_match('/^' . preg_quote($var, '/') . '\s*=/m', $envContent)) {
                    $foundVars[] = $var;
                }
            }

            // Check for common typos
            $commonTypos = [
                'DB_HOST' => ['DBHOST', 'DB_HOSTS', 'DATABASE_HOST', 'HOST'],
                'DB_NAME' => ['DBNAME', 'DB_NAMES', 'DATABASE_NAME', 'DATABASE', 'DB_DATABASE'],
                'DB_USER' => ['DBUSER', 'DB_USERS', 'DATABASE_USER', 'USER', 'DB_USERNAME'],
                'DB_PASS' => ['DBPASS', 'DB_PASSWORD', 'DATABASE_PASS', 'DATABASE_PASSWORD', 'PASSWORD', 'PASS'],
                'DB_PORT' => ['DBPORT', 'DATABASE_PORT', 'PORT']
            ];

            foreach ($expectedVars as $var) {
                if (!in_array($var, $foundVars)) {
                    $possibleTypos = $commonTypos[$var] ?? [];
                    $foundTypo = false;

                    foreach ($possibleTypos as $typo) {
                        if (preg_match('/^' . preg_quote($typo, '/') . '\s*=/m', $envContent)) {
                            $envErrors[] = "Found '$typo' but expected '$var' in .env. Possible typo.";
                            $foundTypo = true;
                            break;
                        }
                    }
                }
            }
        }

        if (!empty($configErrors) || !empty($envErrors)) {
            $message = "Database configuration error";
            if (!empty($configErrors)) {
                $message .= ": " . implode(", ", $configErrors);
            }
            throw new DatabaseException(
                $message,
                500,
                null,
                $configErrors,
                $envErrors
            );
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        // Build options and handle ATTR_INIT_COMMAND deprecation / new constant
        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $initKey = null;
        if (defined('Pdo\\Mysql::ATTR_INIT_COMMAND')) {
            $initKey = constant('Pdo\\Mysql::ATTR_INIT_COMMAND');
        } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $initKey = constant('PDO::MYSQL_ATTR_INIT_COMMAND');
        }

        if ($initKey !== null) {
            $options[$initKey] = "SET NAMES utf8mb4";
        } else {
            $needExecInit = true;
        }

        try {
            $this->dbh = new PDO($dsn, $user, $pass, $options);
            // Jika konstanta tidak tersedia, jalankan perintah inisialisasi manual
            if (isset($needExecInit) && $needExecInit) {
                // Pastikan perintah berjalan tanpa mengganggu eksekusi awal
                try {
                    $this->dbh->exec("SET NAMES utf8mb4");
                } catch (\Throwable $t) {
                    if ($debug) {
                        error_log("[DB INIT] Failed to exec SET NAMES: " . $t->getMessage());
                    }
                }
            }
            $this->isConnected = true;
        } catch (PDOException $e) {
            // Log error untuk debugging
            error_log("Database Connection Error: " . $e->getMessage());

            // Check for common connection errors and provide helpful messages
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            $detailedMessage = "Database connection failed";

            // Provide specific error messages based on error code
            if (strpos($errorMessage, 'Access denied') !== false) {
                $detailedMessage = "Database access denied. Please check DB_USER and DB_PASS in your .env file.";
                $envErrors[] = "Access denied - Check DB_USER and DB_PASS credentials";
            } elseif (strpos($errorMessage, 'Unknown database') !== false) {
                $detailedMessage = "Database '{$dbname}' not found. Please check DB_NAME in your .env file.";
                $envErrors[] = "Database '{$dbname}' does not exist - Check DB_NAME";
            } elseif (strpos($errorMessage, "Can't connect") !== false || $errorCode == 2002) {
                $detailedMessage = "Cannot connect to database server. Please check DB_HOST and DB_PORT in your .env file.";
                $envErrors[] = "Connection failed - Check DB_HOST ({$host}) and DB_PORT ({$port})";
            } else {
                $detailedMessage = $errorMessage;
            }

            throw new DatabaseException(
                $detailedMessage,
                $errorCode,
                $e,
                $configErrors,
                $envErrors
            );
        }
    }

    /**
     * Insert row ke table
     *
     * @param string $table
     * @param array $data
     * @return bool
     * @throws DatabaseException
     */
    public function insert(string $table, array $data): bool
    {
        $this->ensureConnection(true);
        $columns = array_keys($data);
        $columnList = "`" . implode("`, `", array_map([$this, 'escapeIdentifierSimple'], $columns)) . "`";
        $placeholders = implode(", ", array_map(fn($col) => ':' . preg_replace('/[^a-zA-Z0-9_]/', '_', $col), $columns));

        $sql = "INSERT INTO `{$this->escapeIdentifierSimple($table)}` ($columnList) VALUES ($placeholders)";
        $this->query($sql);
        foreach ($data as $key => $value) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
            $this->bind(":$safeKey", $value);
        }
        $result = $this->execute();
        return $result === true;
    }

    /**
     * Update dengan where dasar (AND). Melempar exception jika data kosong atau where kosong.
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return int Jumlah baris yang diupdate
     * @throws DatabaseException
     */
    public function update(string $table, array $data, array $where): int
    {
        $this->ensureConnection(true);
        if (empty($data)) {
            throw new \InvalidArgumentException("UPDATE: data kosong");
        }
        if (empty($where)) {
            throw new \InvalidArgumentException("UPDATE: WHERE kosong (rawan mass update)");
        }

        $setParts = [];
        foreach ($data as $col => $val) {
            $setParts[] = $this->escapeIdentifierSimple($col) . " = :set_$col";
        }

        $whereParts = [];
        foreach ($where as $col => $val) {
            $whereParts[] = $this->escapeIdentifierSimple($col) . " = :where_$col";
        }

        $sql = "UPDATE `{$this->escapeIdentifierSimple($table)}` SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);

        $this->query($sql);

        // bind SET
        foreach ($data as $col => $val) {
            $this->bind(":set_$col", $val);
        }
        // bind WHERE
        foreach ($where as $col => $val) {
            $this->bind(":where_$col", $val);
        }

        $this->execute();
        return $this->rowCount();
    }

    /**
     * Delete dengan kondisi AND
     *
     * @param string $table
     * @param array $where
     * @return int Jumlah baris yang didelete
     * @throws DatabaseException
     */
    public function delete(string $table, array $where): int
    {
        $this->ensureConnection(true);
        if (empty($where)) {
            throw new \InvalidArgumentException("DELETE: WHERE kosong (rawan mass delete)");
        }

        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = $this->escapeIdentifierSimple($key) . " = :where_$key";
        }
        $whereStr = implode(" AND ", $whereParts);

        $sql = "DELETE FROM `{$this->escapeIdentifierSimple($table)}` WHERE $whereStr";
        $this->query($sql);
        foreach ($where as $key => $value) {
            $this->bind(":where_$key", $value);
        }

        $this->execute();
        return $this->rowCount();
    }

    /**
     * SELECT sederhana (tambahan: orderBy, limit, offset opsional)
     *
     * @param string $table
     * @param array $columns
     * @param array $where
     * @param string|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array
     * @throws DatabaseException
     */
    public function select(
        string $table,
        array $columns = ['*'],
        array $where = [],
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $this->ensureConnection(true);
        $columnList = implode(", ", array_map(function ($c) {
            return $c === '*' ? $c : $this->escapeIdentifierSimple($c);
        }, $columns));

        $sql = "SELECT $columnList FROM `{$this->escapeIdentifierSimple($table)}`";
        if (!empty($where)) {
            $whereParts = [];
            foreach ($where as $key => $value) {
                $whereParts[] = $this->escapeIdentifierSimple($key) . " = :where_$key";
            }
            $sql .= " WHERE " . implode(" AND ", $whereParts);
        }

        if ($orderBy) {
            $sql .= " ORDER BY " . $orderBy; // caller bertanggung jawab agar $orderBy aman
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET " . (int) $offset;
        }

        $this->query($sql);
        foreach ($where as $key => $value) {
            $this->bind(":where_$key", $value);
        }
        return $this->resultSet();
    }

    /**
     * Prepare statement
     *
     * @param string $sql
     * @return self
     * @throws DatabaseException
     */
    public function query(string $sql): self
    {
        $this->ensureConnection(true);
        // Jika ada kelas Config, manfaatkan DEBUG_MODE
        $debug = false;
        if (class_exists('\\TheFramework\\App\\Core\\Config')) {
            $debug = \TheFramework\App\Core\Config::get('DEBUG_MODE', false);
        }

        if ($debug) {
            error_log("[SQL] " . $sql);
        }
        $this->queryLog[] = ['query' => $sql, 'bindings' => [], 'time' => microtime(true)];
        try {
            $this->stmt = $this->dbh->prepare($sql);
            if ($this->stmt === false) {
                throw new PDOException("Failed to prepare statement: $sql");
            }
        } catch (PDOException $e) {
            error_log("Prepare Statement Error: " . $e->getMessage());
            throw $e;
        }

        return $this;
    }

    /**
     * Bind value ke statement.
     * PERHATIAN: Panggil query() terlebih dahulu sebelum memanggil bind()!
     * Lakukan sesuai urutan: query() -> bind() -> execute()
     *
     * @param string $param
     * @param mixed $value
     * @param int|null $type
     * @return void
     * @throws \LogicException Jika dipanggil sebelum query()
     */
    public function bind(string $param, $value, ?int $type = null): void
    {
        if ($this->stmt === null) {
            throw new \LogicException("Harus memanggil query() terlebih dahulu sebelum bind(). Urutan yang benar: query() -> bind() -> execute()");
        }
        $debug = (class_exists('\\TheFramework\\App\\Core\\Config') ? \TheFramework\App\Core\Config::get('DEBUG_MODE', false) : false);
        if ($debug) {
            error_log("[BIND] $param = " . (is_scalar($value) ? (string) $value : gettype($value)));
        }

        $logIndex = count($this->queryLog) - 1;
        if ($logIndex >= 0) {
            $this->queryLog[$logIndex]['bindings'][$param] = $value;
        }

        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Execute prepared statement
     *
     * @return bool
     * @throws DatabaseException|PDOException
     */
    public function execute(): bool
    {
        $this->ensureConnection(true);
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $errorMessage = "Database operation failed:\n" .
                "Message: " . $e->getMessage() . "\n" .
                "SQL: " . (property_exists($this->stmt, 'queryString') ? $this->stmt->queryString : 'N/A') . "\n";
            error_log($errorMessage);
            // Bungkus ulang exception agar stacktrace jelas
            throw new PDOException($errorMessage, (int) $e->getCode(), $e);
        }
    }

    /**
     * Ambil banyak baris
     *
     * @return array
     * @throws DatabaseException
     */
    public function resultSet(): array
    {
        $this->ensureConnection(true);
        $this->execute();
        $result = $this->stmt->fetchAll();
        $this->stmt->closeCursor();
        return $result;
    }

    /**
     * Ambil satu baris
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function single()
    {
        $this->ensureConnection(true);
        $this->execute();
        $result = $this->stmt->fetch();
        $this->stmt->closeCursor();
        return $result;
    }

    /**
     * Row count dari statement terakhir
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Mulai transaksi (jika belum)
     *
     * @return bool
     * @throws DatabaseException
     */
    public function beginTransaction(): bool
    {
        $this->ensureConnection(true);
        if (!$this->dbh->inTransaction()) {
            return $this->dbh->beginTransaction();
        }
        return true;
    }

    /**
     * Commit transaksi
     *
     * @return bool
     * @throws DatabaseException
     */
    public function commit(): bool
    {
        $this->ensureConnection(true);
        return $this->dbh->commit();
    }

    /**
     * Rollback transaksi
     *
     * @return bool
     * @throws DatabaseException
     */
    public function rollBack(): bool
    {
        $this->ensureConnection(true);
        return $this->dbh->rollBack();
    }

    /**
     * Quote value aman
     *
     * @param mixed $value
     * @return string
     */
    public function quote($value): string
    {
        $this->ensureConnection(true);
        if (is_null($value)) {
            return 'NULL';
        }
        return $this->dbh->quote($value);
    }

    /**
     * Ambil last insert id
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Savepoint (dengan sanitasi sederhana)
     *
     * @param string $name
     * @return void
     */
    public function savepoint(string $name): void
    {
        $clean = $this->sanitizeSavepointName($name);
        $this->dbh->exec("SAVEPOINT `{$clean}`");
    }

    /**
     * Rollback to savepoint
     *
     * @param string $name
     * @return void
     */
    public function rollbackTo(string $name): void
    {
        $clean = $this->sanitizeSavepointName($name);
        $this->dbh->exec("ROLLBACK TO SAVEPOINT `{$clean}`");
    }

    /**
     * Release savepoint
     *
     * @param string $name
     * @return void
     */
    public function releaseSavepoint(string $name): void
    {
        $clean = $this->sanitizeSavepointName($name);
        $this->dbh->exec("RELEASE SAVEPOINT `{$clean}`");
    }

    /**
     * Sanitasi nama savepoint agar aman dari karakter tidak diinginkan
     *
     * @param string $name
     * @return string
     */
    private function sanitizeSavepointName(string $name): string
    {
        // hanya izinkan alfanumerik dan underscore
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Simple escape identifier (kolom / table) - tidak untuk input user mentah
     * Gunakan dengan hati-hati: ini melakukan escaping backtick sederhana.
     *
     * @param string $name
     * @return string
     */
    private function escapeIdentifierSimple(string $name): string
    {
        return str_replace('`', '``', $name);
    }

    /**
     * Mencegah cloning
     */
    private function __clone()
    {
    }

    /**
     * Mencegah unserialize
     */
    public function __wakeup()
    {
        throw new PDOException("Cannot unserialize database connection");
    }
    /**
     * Get executed query log
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Clear query log
     */
    public function flushQueryLog(): void
    {
        $this->queryLog = [];
    }

    // ========================================================
    //  TRANSACTION WITH CLOSURE (Auto commit/rollback)
    // ========================================================

    /**
     * Execute a Closure within a transaction.
     * Auto-commit jika sukses, auto-rollback jika exception.
     * 
     * @param \Closure $callback
     * @param int $attempts Jumlah percobaan (untuk deadlock retry)
     * @return mixed Result dari callback
     * @throws \Throwable
     */
    public function transaction(\Closure $callback, int $attempts = 1): mixed
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->beginTransaction();

            try {
                $result = $callback($this);
                $this->commit();
                return $result;
            } catch (\Throwable $e) {
                $this->rollBack();

                // Retry jika deadlock (MySQL error 1213, 1205)
                if ($attempt < $attempts && $this->isDeadlockError($e)) {
                    usleep(100000 * $attempt); // Exponential backoff
                    continue;
                }

                throw $e;
            }
        }

        throw new \RuntimeException('Transaction failed after all attempts.');
    }

    /**
     * Check apakah error adalah deadlock
     */
    protected function isDeadlockError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        return str_contains($message, 'deadlock') || str_contains($message, 'lock wait timeout');
    }

    // ========================================================
    //  RAW QUERIES
    // ========================================================

    /**
     * Execute raw SQL statement (DDL atau non-query)
     * 
     * Contoh: DB::statement('ALTER TABLE users ADD COLUMN age INT')
     */
    public function statement(string $sql, array $bindings = []): bool
    {
        $this->ensureConnection(true);
        $this->query($sql);
        foreach ($bindings as $key => $value) {
            $param = is_int($key) ? $key + 1 : ':' . $key;
            $this->bind($param, $value);
        }
        return $this->execute();
    }

    /**
     * Execute unprepared SQL (tanpa binding, hati-hati SQL injection!)
     * 
     * Untuk DDL, multi-statement, atau bulk operations
     */
    public function unprepared(string $sql): bool
    {
        $this->ensureConnection(true);
        return $this->dbh->exec($sql) !== false;
    }

    /**
     * Create a raw SQL expression
     * 
     * Contoh: DB::raw('NOW()')
     */
    public static function raw(string $value): Raw
    {
        return new Raw($value);
    }

    // ========================================================
    //  CONNECTION INFO
    // ========================================================

    /**
     * Get driver name (mysql, pgsql, sqlite, dll)
     */
    public function getDriverName(): string
    {
        $this->ensureConnection(true);
        return $this->dbh->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get PDO instance langsung
     */
    public function getPdo(): ?\PDO
    {
        $this->ensureConnection(true);
        return $this->dbh;
    }

    /**
     * Get server version
     */
    public function getServerVersion(): string
    {
        $this->ensureConnection(true);
        return $this->dbh->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get database name saat ini
     */
    public function getDatabaseName(): string
    {
        return Config::get('DB_DATABASE', '');
    }

    /**
     * Reconnect to database
     */
    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Disconnect from database
     */
    public function disconnect(): void
    {
        $this->dbh = null;
    }

    // ========================================================
    //  SHORTCUT HELPERS
    // ========================================================

    /**
     * Get QueryBuilder instance untuk table
     * 
     * Contoh: Database::getInstance()->table('users')->where('id', 1)->first()
     */
    public function table(string $table): QueryBuilder
    {
        $qb = new QueryBuilder($this);
        return $qb->table($table);
    }

    /**
     * Check if table exists
     */
    public function tableExists(string $table): bool
    {
        $this->ensureConnection(true);
        try {
            $driver = $this->getDriverName();
            if ($driver === 'sqlite') {
                $this->query("SELECT name FROM sqlite_master WHERE type='table' AND name=:t");
                $this->bind(':t', $table);
            } else {
                $this->query("SHOW TABLES LIKE :t");
                $this->bind(':t', $table);
            }
            $this->execute();
            return $this->single() !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Benchmark a query callback — return result + elapsed time
     */
    public function benchmark(\Closure $callback): array
    {
        $start = microtime(true);
        $result = $callback($this);
        $elapsed = round((microtime(true) - $start) * 1000, 2);

        return [
            'result' => $result,
            'time_ms' => $elapsed,
        ];
    }

    /**
     * Get formatted summary
     */
    public function summary(): string
    {
        $lines = [];
        $lines[] = "📊 Database Summary";
        $lines[] = str_repeat('─', 40);
        $lines[] = "   Connected:  " . ($this->isConnected() ? 'Yes' : 'No');
        $lines[] = "   Enabled:    " . ($this->isEnabled() ? 'Yes' : 'No');
        $lines[] = "   Database:   " . $this->getDatabaseName();
        if ($this->isConnected()) {
            $lines[] = "   Driver:     " . $this->getDriverName();
            $lines[] = "   Version:    " . $this->getServerVersion();
        }
        $lines[] = "   Queries:    " . count($this->queryLog);

        return implode("\n", $lines);
    }
}
