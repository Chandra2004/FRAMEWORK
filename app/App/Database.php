<?php

namespace TheFramework\App;

use PDO;
use PDOException;

class Database
{
    private static ?self $instance = null;
    private PDO $dbh;
    private $stmt;

    private function __construct()
    {
        $this->connect();
    }

    /**
     * Mendapatkan instance singleton
     *
     * @return Database
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Buat koneksi PDO
     *
     * Menangani perubahan konstanta ATTR_INIT_COMMAND (PHP 8.5+)
     */
    private function connect(): void
    {
        // Pastikan Config::loadEnv() dan Config::get() tersedia pada aplikasi Anda.
        if (class_exists('\\TheFramework\\App\\Config')) {
            Config::loadEnv();
            $host = Config::get('DB_HOST');
            $dbname = Config::get('DB_NAME');
            $user = Config::get('DB_USER');
            $pass = Config::get('DB_PASS');
            $debug = Config::get('DEBUG_MODE', false);
        } else {
            // Jika Anda tidak memakai Config, ubah sesuai kebutuhan.
            throw new PDOException("Config class tidak ditemukan. Pastikan \\TheFramework\\App\\Config tersedia.");
        }

        if (empty($host) || empty($dbname) || empty($user)) {
            throw new PDOException("Database configuration is incomplete");
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        // Build options and handle ATTR_INIT_COMMAND deprecation / new constant
        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // Try to use the new class-constant if available: Pdo\Mysql::ATTR_INIT_COMMAND
        // Fallback to PDO::MYSQL_ATTR_INIT_COMMAND if present.
        // We resolve the constant's *value* (an int) and use it as key in $options.
        $initKey = null;
        // check for new constant (class constant) first
        if (defined('Pdo\\Mysql::ATTR_INIT_COMMAND')) {
            $initKey = constant('Pdo\\Mysql::ATTR_INIT_COMMAND');
        } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $initKey = constant('PDO::MYSQL_ATTR_INIT_COMMAND');
        }

        if ($initKey !== null) {
            // Set init command (safe to use). Jangan gunakan string 'PDO::...' sebagai key.
            $options[$initKey] = "SET NAMES utf8mb4";
        } else {
            // Jika tidak ada, set via exec setelah koneksi dibuat.
            $needExecInit = true;
        }

        try {
            $this->dbh = new PDO($dsn, $user, $pass, $options);
            // Jika konstanta tidak tersedia, jalankan perintah inisialisasi manual
            if (!isset($needExecInit) || empty($needExecInit) === false) {
                // Pastikan perintah berjalan tanpa mengganggu eksekusi awal
                try {
                    $this->dbh->exec("SET NAMES utf8mb4");
                } catch (\Throwable $t) {
                    if ($debug) {
                        error_log("[DB INIT] Failed to exec SET NAMES: " . $t->getMessage());
                    }
                }
            }
        } catch (PDOException $e) {
            // Log error untuk debugging, tapi jangan expose detail ke user
            error_log("Database Connection Error: " . $e->getMessage());
            throw new PDOException("Database connection failed. Check error logs for details.");
        }
    }

    /**
     * Insert row ke table
     *
     * @param string $table
     * @param array $data
     * @return bool
     */
    public function insert(string $table, array $data): bool
    {
        $columns = array_keys($data);
        $columnList = "`" . implode("`, `", array_map([$this, 'escapeIdentifierSimple'], $columns)) . "`";
        $placeholders = ":" . implode(", :", $columns);

        $sql = "INSERT INTO `{$this->escapeIdentifierSimple($table)}` ($columnList) VALUES ($placeholders)";
        $this->query($sql);
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
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
     * @return bool
     */
    public function update(string $table, array $data, array $where): bool
    {
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

        return $this->execute();
    }

    /**
     * Delete dengan kondisi AND
     *
     * @param string $table
     * @param array $where
     * @return bool
     */
    public function delete(string $table, array $where): bool
    {
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
        return $this->execute();
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
     */
    public function select(
        string $table,
        array $columns = ['*'],
        array $where = [],
        ?string $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
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
            $sql .= " LIMIT " . (int)$limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
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
     * @return void
     */
    public function query(string $sql): void
    {
        // Jika ada kelas Config, manfaatkan DEBUG_MODE
        $debug = false;
        if (class_exists('\\TheFramework\\App\\Config')) {
            $debug = \TheFramework\App\Config::get('DEBUG_MODE', false);
        }

        if ($debug) {
            error_log("[SQL] " . $sql);
        }
        try {
            $this->stmt = $this->dbh->prepare($sql);
            if ($this->stmt === false) {
                throw new PDOException("Failed to prepare statement: $sql");
            }
        } catch (PDOException $e) {
            error_log("Prepare Statement Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Bind value ke statement
     *
     * @param string $param
     * @param mixed $value
     * @param int|null $type
     * @return void
     */
    public function bind(string $param, $value, ?int $type = null): void
    {
        $debug = (class_exists('\\TheFramework\\App\\Config') ? \TheFramework\App\Config::get('DEBUG_MODE', false) : false);
        if ($debug) {
            error_log("[BIND] $param = " . (is_scalar($value) ? (string)$value : gettype($value)));
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
     * @throws PDOException
     */
    public function execute(): bool
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            $errorMessage = "Database operation failed:\n" .
                "Message: " . $e->getMessage() . "\n" .
                "SQL: " . (property_exists($this->stmt, 'queryString') ? $this->stmt->queryString : 'N/A') . "\n";
            error_log($errorMessage);
            // Bungkus ulang exception agar stacktrace jelas
            throw new PDOException($errorMessage, (int)$e->getCode(), $e);
        }
    }

    /**
     * Ambil banyak baris
     *
     * @return array
     */
    public function resultSet(): array
    {
        $this->execute();
        $result = $this->stmt->fetchAll();
        $this->stmt->closeCursor();
        return $result;
    }

    /**
     * Ambil satu baris
     *
     * @return mixed
     */
    public function single()
    {
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
     */
    public function beginTransaction(): bool
    {
        if (!$this->dbh->inTransaction()) {
            return $this->dbh->beginTransaction();
        }
        return true;
    }

    /**
     * Commit transaksi
     *
     * @return bool
     */
    public function commit(): bool
    {
        return $this->dbh->commit();
    }

    /**
     * Rollback transaksi
     *
     * @return bool
     */
    public function rollBack(): bool
    {
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
}
