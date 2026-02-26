<?php

namespace TheFramework\App\Schema;

use Closure;
use TheFramework\App\Database\Database;

/**
 * Schema — DDL Facade & Database Introspection
 * 
 * Fasad utama untuk operasi DDL (Data Definition Language):
 * create, alter, drop, rename tabel, serta introspeksi schema database.
 * 
 * Terinspirasi dari Laravel Schema Builder, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Schema
 * @version 5.0.1
 */
class Schema
{
    // ========================================================
    //  TABLE OPERATIONS
    // ========================================================

    /**
     * Buat tabel baru
     */
    public static function create(string $table, Closure $callback): void
    {
        $db = Database::getInstance();
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $keyword = $blueprint->isTemporary() ? 'CREATE TEMPORARY TABLE' : 'CREATE TABLE';
        $sql = "$keyword IF NOT EXISTS `$table` (";
        $sql .= implode(', ', $blueprint->getColumns());

        if ($blueprint->getPrimaryKey()) {
            $sql .= ', PRIMARY KEY (' . $blueprint->getPrimaryKey() . ')';
        }

        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $sql .= ", $foreignKey";
        }

        $sql .= ')';

        // Table options
        $engine = $blueprint->getEngine() ?: 'InnoDB';
        $sql .= " ENGINE=$engine";

        $charset = $blueprint->getCharset() ?: 'utf8mb4';
        $sql .= " DEFAULT CHARSET=$charset";

        if ($collation = $blueprint->getCollation()) {
            $sql .= " COLLATE=$collation";
        } else {
            $sql .= ' COLLATE=utf8mb4_unicode_ci';
        }

        if ($comment = $blueprint->getTableComment()) {
            $sql .= " COMMENT='" . addslashes($comment) . "'";
        }

        $sql .= ';';

        $db->query($sql);
        $db->execute();
    }

    /**
     * Alter tabel yang sudah ada
     */
    public static function table(string $table, Closure $callback): void
    {
        $db = Database::getInstance();
        $blueprint = new Blueprint($table);
        $blueprint->setAlterMode();
        $callback($blueprint);

        // Finalize pending foreign keys
        $blueprint->getForeignKeys();

        $statements = $blueprint->getAlterStatements();
        if (empty($statements))
            return;

        // Batch semua alter statements dalam satu query (lebih efisien)
        $sql = "ALTER TABLE `$table` " . implode(', ', $statements) . ';';
        $db->query($sql);
        $db->execute();
    }

    /**
     * Drop tabel jika ada
     */
    public static function dropIfExists(string $table): void
    {
        $db = Database::getInstance();
        $db->query("DROP TABLE IF EXISTS `$table`;");
        $db->execute();
    }

    /**
     * Drop tabel (strict — error jika tidak ada)
     */
    public static function drop(string $table): void
    {
        $db = Database::getInstance();
        $db->query("DROP TABLE `$table`;");
        $db->execute();
    }

    /**
     * Drop banyak tabel sekaligus
     */
    public static function dropMany(array $tables): void
    {
        if (empty($tables))
            return;
        $db = Database::getInstance();
        $tableList = '`' . implode('`, `', $tables) . '`';
        $db->query("DROP TABLE IF EXISTS $tableList;");
        $db->execute();
    }

    /**
     * Rename tabel
     */
    public static function rename(string $from, string $to): void
    {
        $db = Database::getInstance();
        $db->query("RENAME TABLE `$from` TO `$to`;");
        $db->execute();
    }

    // ========================================================
    //  SCHEMA INTROSPECTION
    // ========================================================

    /**
     * Cek apakah tabel ada
     */
    public static function hasTable(string $table): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT 1 FROM `$table` LIMIT 1;");
            $db->execute();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Cek apakah kolom ada di tabel
     */
    public static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, static::getColumnListing($table));
    }

    /**
     * Cek apakah beberapa kolom ada di tabel
     */
    public static function hasColumns(string $table, array $columns): bool
    {
        $existing = static::getColumnListing($table);
        foreach ($columns as $column) {
            if (!in_array($column, $existing)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Dapatkan daftar semua nama kolom di tabel
     */
    public static function getColumnListing(string $table): array
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW COLUMNS FROM `$table`;");
            $rows = $db->resultSet();
            return array_column($rows, 'Field');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Dapatkan tipe data kolom
     */
    public static function getColumnType(string $table, string $column): ?string
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW COLUMNS FROM `$table` WHERE Field = :col;");
            $db->bind(':col', $column);
            $row = $db->single();
            return $row ? $row['Type'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan detail lengkap semua kolom di tabel
     * (nama, tipe, null, key, default, extra)
     */
    public static function getColumns(string $table): array
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW FULL COLUMNS FROM `$table`;");
            return $db->resultSet();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Dapatkan detail satu kolom
     */
    public static function getColumn(string $table, string $column): ?array
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW FULL COLUMNS FROM `$table` WHERE Field = :col;");
            $db->bind(':col', $column);
            return $db->single() ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan daftar semua tabel di database
     */
    public static function getTables(): array
    {
        try {
            $db = Database::getInstance();
            $db->query('SHOW TABLES;');
            $rows = $db->resultSet();
            return array_map(fn($row) => array_values($row)[0], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Dapatkan daftar semua views di database
     */
    public static function getViews(): array
    {
        try {
            $db = Database::getInstance();
            $db->query('SHOW FULL TABLES WHERE Table_type = :type;');
            $db->bind(':type', 'VIEW');
            $rows = $db->resultSet();
            return array_map(fn($row) => array_values($row)[0], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Dapatkan daftar index pada tabel
     */
    public static function getIndexes(string $table): array
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW INDEX FROM `$table`;");
            return $db->resultSet();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Cek apakah index ada
     */
    public static function hasIndex(string $table, string $indexName): bool
    {
        $indexes = static::getIndexes($table);
        foreach ($indexes as $idx) {
            if ($idx['Key_name'] === $indexName)
                return true;
        }
        return false;
    }

    /**
     * Dapatkan daftar foreign keys pada tabel
     */
    public static function getForeignKeys(string $table): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME, 
                           DELETE_RULE, UPDATE_RULE 
                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
                    JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc 
                        ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME 
                        AND kcu.TABLE_SCHEMA = rc.CONSTRAINT_SCHEMA
                    WHERE kcu.TABLE_NAME = :table 
                        AND kcu.TABLE_SCHEMA = DATABASE()
                        AND kcu.REFERENCED_TABLE_NAME IS NOT NULL;";
            $db->query($sql);
            $db->bind(':table', $table);
            return $db->resultSet();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Dapatkan ukuran tabel dalam bytes
     */
    public static function getTableSize(string $table): ?int
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT (DATA_LENGTH + INDEX_LENGTH) AS size 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME = :table AND TABLE_SCHEMA = DATABASE();";
            $db->query($sql);
            $db->bind(':table', $table);
            $row = $db->single();
            return $row ? (int) $row['size'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan jumlah baris (estimasi) di tabel
     */
    public static function getTableRowCount(string $table): ?int
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME = :table AND TABLE_SCHEMA = DATABASE();";
            $db->query($sql);
            $db->bind(':table', $table);
            $row = $db->single();
            return $row ? (int) $row['TABLE_ROWS'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan engine tabel
     */
    public static function getTableEngine(string $table): ?string
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT ENGINE FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME = :table AND TABLE_SCHEMA = DATABASE();";
            $db->query($sql);
            $db->bind(':table', $table);
            $row = $db->single();
            return $row ? $row['ENGINE'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan CREATE TABLE statement
     */
    public static function getCreateTable(string $table): ?string
    {
        try {
            $db = Database::getInstance();
            $db->query("SHOW CREATE TABLE `$table`;");
            $row = $db->single();
            return $row ? $row['Create Table'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    // ========================================================
    //  CONDITIONAL SCHEMA OPERATIONS
    // ========================================================

    /**
     * Jalankan callback hanya jika tabel ada
     */
    public static function whenTableHasColumn(string $table, string $column, Closure $callback): void
    {
        if (static::hasColumn($table, $column)) {
            $callback();
        }
    }

    /**
     * Jalankan callback hanya jika kolom TIDAK ada
     */
    public static function whenTableDoesntHaveColumn(string $table, string $column, Closure $callback): void
    {
        if (!static::hasColumn($table, $column)) {
            $callback();
        }
    }

    /**
     * Create tabel hanya jika belum ada, skip jika sudah
     */
    public static function createIfNotExists(string $table, Closure $callback): bool
    {
        if (static::hasTable($table)) {
            return false;
        }
        static::create($table, $callback);
        return true;
    }

    // ========================================================
    //  FOREIGN KEY CONSTRAINTS TOGGLE
    // ========================================================

    /**
     * Disable foreign key checks
     */
    public static function disableForeignKeyConstraints(): void
    {
        $db = Database::getInstance();
        $db->query('SET FOREIGN_KEY_CHECKS = 0;');
        $db->execute();
    }

    /**
     * Enable foreign key checks
     */
    public static function enableForeignKeyConstraints(): void
    {
        $db = Database::getInstance();
        $db->query('SET FOREIGN_KEY_CHECKS = 1;');
        $db->execute();
    }

    /**
     * Jalankan callback dengan FK checks dimatikan, lalu nyalakan kembali
     */
    public static function withoutForeignKeyConstraints(Closure $callback): mixed
    {
        static::disableForeignKeyConstraints();
        try {
            $result = $callback();
            return $result;
        } finally {
            static::enableForeignKeyConstraints();
        }
    }

    // ========================================================
    //  VIEW OPERATIONS
    // ========================================================

    /**
     * Buat atau replace database view
     */
    public static function createView(string $viewName, $query): void
    {
        $db = Database::getInstance();

        if ($query instanceof \TheFramework\App\Database\QueryBuilder) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();

            foreach ($bindings as $key => $value) {
                $value = is_string($value) ? $db->quote($value) : (is_null($value) ? 'NULL' : $value);
                if (strpos($key, ':') === 0) {
                    $sql = str_replace($key, $value, $sql);
                }
            }
            $query = $sql;
        }

        $sql = "CREATE OR REPLACE VIEW `$viewName` AS $query;";
        $db->query($sql);
        $db->execute();
    }

    /**
     * Drop view
     */
    public static function dropView(string $viewName): void
    {
        $db = Database::getInstance();
        $db->query("DROP VIEW IF EXISTS `$viewName`;");
        $db->execute();
    }

    /**
     * Cek apakah view ada
     */
    public static function hasView(string $viewName): bool
    {
        return in_array($viewName, static::getViews());
    }

    // ========================================================
    //  BULK INSERT (Helper untuk Seeder)
    // ========================================================

    /**
     * Bulk insert data ke tabel
     */
    public static function insert(string $table, array $rows): void
    {
        if (empty($rows))
            return;

        $db = Database::getInstance();
        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';

        // Gunakan prepared statements untuk keamanan
        $placeholders = [];
        $allBindings = [];
        foreach ($rows as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $paramName = ":{$col}_{$i}";
                $rowPlaceholders[] = $paramName;
                $allBindings[$paramName] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = "INSERT INTO `$table` ($columnList) VALUES " . implode(', ', $placeholders) . ';';

        $db->query($sql);
        foreach ($allBindings as $param => $value) {
            $db->bind($param, $value);
        }
        $db->execute();
    }

    /**
     * Insert or update (UPSERT)
     */
    public static function upsert(string $table, array $rows, array $uniqueColumns, array $updateColumns): void
    {
        if (empty($rows))
            return;

        $db = Database::getInstance();
        $columns = array_keys($rows[0]);
        $columnList = '`' . implode('`, `', $columns) . '`';

        $placeholders = [];
        $allBindings = [];
        foreach ($rows as $i => $row) {
            $rowPlaceholders = [];
            foreach ($columns as $col) {
                $paramName = ":{$col}_{$i}";
                $rowPlaceholders[] = $paramName;
                $allBindings[$paramName] = $row[$col] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $updateParts = [];
        foreach ($updateColumns as $col) {
            $updateParts[] = "`$col` = VALUES(`$col`)";
        }

        $sql = "INSERT INTO `$table` ($columnList) VALUES " . implode(', ', $placeholders);
        $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts) . ';';

        $db->query($sql);
        foreach ($allBindings as $param => $value) {
            $db->bind($param, $value);
        }
        $db->execute();
    }

    /**
     * Truncate tabel (hapus semua data, reset auto-increment)
     */
    public static function truncate(string $table): void
    {
        $db = Database::getInstance();
        $db->query("TRUNCATE TABLE `$table`;");
        $db->execute();
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — DATABASE UTILITIES
    // ========================================================

    /**
     * Dapatkan nama database saat ini
     */
    public static function getDatabaseName(): ?string
    {
        try {
            $db = Database::getInstance();
            $db->query('SELECT DATABASE() AS db;');
            $row = $db->single();
            return $row ? $row['db'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan versi MySQL/MariaDB server
     */
    public static function getServerVersion(): ?string
    {
        try {
            $db = Database::getInstance();
            $db->query('SELECT VERSION() AS version;');
            $row = $db->single();
            return $row ? $row['version'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan ukuran total database dalam bytes
     */
    public static function getDatabaseSize(): ?int
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS size 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE();";
            $db->query($sql);
            $row = $db->single();
            return $row ? (int) $row['size'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Dapatkan statistik lengkap semua tabel
     */
    public static function getTableStats(): array
    {
        try {
            $db = Database::getInstance();
            $sql = "SELECT TABLE_NAME, ENGINE, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, 
                           AUTO_INCREMENT, CREATE_TIME, UPDATE_TIME, TABLE_COMMENT
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
                    ORDER BY TABLE_NAME;";
            $db->query($sql);
            return $db->resultSet();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Compare dua tabel (schema diff) — BEYOND LARAVEL
     * 
     * @return array ['added' => [...], 'removed' => [...], 'modified' => [...]]
     */
    public static function diffTables(string $table1, string $table2): array
    {
        $cols1 = static::getColumns($table1);
        $cols2 = static::getColumns($table2);

        $map1 = [];
        foreach ($cols1 as $col) {
            $map1[$col['Field']] = $col;
        }
        $map2 = [];
        foreach ($cols2 as $col) {
            $map2[$col['Field']] = $col;
        }

        $added = [];
        $removed = [];
        $modified = [];

        // Kolom yang ada di table2 tapi tidak di table1
        foreach ($map2 as $name => $col) {
            if (!isset($map1[$name])) {
                $added[] = $col;
            }
        }

        // Kolom yang ada di table1 tapi tidak di table2
        foreach ($map1 as $name => $col) {
            if (!isset($map2[$name])) {
                $removed[] = $col;
            }
        }

        // Kolom yang ada di keduanya tapi berbeda tipe/definisi
        foreach ($map1 as $name => $col1) {
            if (isset($map2[$name])) {
                $col2 = $map2[$name];
                if ($col1['Type'] !== $col2['Type'] || $col1['Null'] !== $col2['Null'] || $col1['Default'] !== $col2['Default'] || $col1['Extra'] !== $col2['Extra']) {
                    $modified[] = [
                        'column' => $name,
                        'table1' => $col1,
                        'table2' => $col2,
                    ];
                }
            }
        }

        return compact('added', 'removed', 'modified');
    }

    /**
     * Generate Blueprint dari tabel yang sudah ada (reverse engineering) — BEYOND LARAVEL
     */
    public static function dumpBlueprint(string $table): string
    {
        $columns = static::getColumns($table);
        $lines = [];
        $lines[] = "Schema::create('$table', function (Blueprint \$table) {";

        foreach ($columns as $col) {
            $field = $col['Field'];
            $type = $col['Type'];
            $null = $col['Null'] === 'YES';
            $default = $col['Default'];
            $extra = $col['Extra'] ?? '';

            $line = '    $table->';

            // Auto-increment
            if (stripos($extra, 'auto_increment') !== false) {
                if (stripos($type, 'bigint') !== false) {
                    $line .= "bigIncrements('$field')";
                } else {
                    $line .= "increments('$field')";
                }
            } elseif (preg_match('/^varchar\((\d+)\)/i', $type, $m)) {
                $line .= "string('$field', {$m[1]})";
            } elseif (stripos($type, 'longtext') !== false) {
                $line .= "longText('$field')";
            } elseif (stripos($type, 'mediumtext') !== false) {
                $line .= "mediumText('$field')";
            } elseif (stripos($type, 'tinytext') !== false) {
                $line .= "tinyText('$field')";
            } elseif (stripos($type, 'text') !== false) {
                $line .= "text('$field')";
            } elseif (stripos($type, 'tinyint(1)') !== false) {
                $line .= "boolean('$field')";
            } elseif (preg_match('/^(tiny|small|medium|big)?int/i', $type, $m)) {
                $prefix = strtolower($m[1] ?? '');
                $unsigned = stripos($type, 'unsigned') !== false;
                $method = match ($prefix) {
                    'tiny' => 'tinyInteger',
                    'small' => 'smallInteger',
                    'medium' => 'mediumInteger',
                    'big' => 'bigInteger',
                    default => 'integer',
                };
                if ($unsigned)
                    $method = 'unsigned' . ucfirst($method);
                $line .= "$method('$field')";
            } elseif (preg_match('/^decimal\((\d+),(\d+)\)/i', $type, $m)) {
                $line .= "decimal('$field', {$m[1]}, {$m[2]})";
            } elseif (stripos($type, 'double') !== false) {
                $line .= "double('$field')";
            } elseif (stripos($type, 'float') !== false) {
                $line .= "float('$field')";
            } elseif (stripos($type, 'datetime') !== false) {
                $line .= "datetime('$field')";
            } elseif (stripos($type, 'timestamp') !== false) {
                $line .= "timestamp('$field')";
            } elseif (stripos($type, 'date') !== false) {
                $line .= "date('$field')";
            } elseif (stripos($type, 'time') !== false) {
                $line .= "time('$field')";
            } elseif (stripos($type, 'year') !== false) {
                $line .= "year('$field')";
            } elseif (stripos($type, 'json') !== false) {
                $line .= "json('$field')";
            } elseif (stripos($type, 'char(36)') !== false) {
                $line .= "uuid('$field')";
            } elseif (preg_match('/^char\((\d+)\)/i', $type, $m)) {
                $line .= "char('$field', {$m[1]})";
            } elseif (preg_match('/^enum\((.+)\)/i', $type, $m)) {
                $line .= "enum('$field', [$m[1]])";
            } elseif (stripos($type, 'blob') !== false) {
                $line .= "blob('$field')";
            } else {
                $line .= "rawColumn('$field', '$type')";
            }

            // Modifiers
            if ($null)
                $line .= '->nullable()';
            if ($default !== null && stripos($extra, 'auto_increment') === false) {
                if ($default === 'CURRENT_TIMESTAMP') {
                    $line .= "->useCurrent()";
                } else {
                    $line .= "->default(" . var_export($default, true) . ")";
                }
            }

            $line .= ';';
            $lines[] = $line;
        }

        $lines[] = '});';
        return implode("\n", $lines);
    }
}
