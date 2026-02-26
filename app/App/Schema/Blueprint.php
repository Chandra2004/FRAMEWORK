<?php

namespace TheFramework\App\Schema;

/**
 * Blueprint — Schema Definition Builder
 * 
 * Mendefinisikan struktur kolom, index, dan constraint untuk tabel database.
 * Terinspirasi dari Laravel Blueprint, ditambah fitur ekstra.
 * 
 * @package TheFramework\App\Schema
 * @version 5.0.1
 */
class Blueprint
{
    private string $table;
    private array $columns = [];
    private ?string $primaryKey = null;
    private array $foreignKeys = [];
    private ?array $pendingForeign = null;
    private bool $alterMode = false;
    private array $alterStatements = [];
    private ?string $lastAddedColumn = null;
    private ?string $engine = null;
    private ?string $charset = null;
    private ?string $collation = null;
    private ?string $tableComment = null;
    private bool $temporary = false;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // ========================================================
    //  GETTERS & MODE
    // ========================================================

    public function getTable(): string
    {
        return $this->table;
    }

    public function setAlterMode(): void
    {
        $this->alterMode = true;
    }

    public function isAlterMode(): bool
    {
        return $this->alterMode;
    }

    public function getAlterStatements(): array
    {
        return $this->alterStatements;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    public function getForeignKeys(): array
    {
        if ($this->pendingForeign) {
            $this->finalizeForeignKey();
        }
        return $this->foreignKeys;
    }

    public function getEngine(): ?string
    {
        return $this->engine;
    }

    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function getTableComment(): ?string
    {
        return $this->tableComment;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    // ========================================================
    //  TABLE OPTIONS (Beyond Laravel)
    // ========================================================

    /**
     * Set storage engine (InnoDB, MyISAM, MEMORY, dll)
     */
    public function engine(string $engine): static
    {
        $this->engine = $engine;
        return $this;
    }

    /**
     * Set character set untuk tabel
     */
    public function charset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * Set collation untuk tabel
     */
    public function collation(string $collation): static
    {
        $this->collation = $collation;
        return $this;
    }

    /**
     * Set komentar pada tabel
     */
    public function comment(string $comment): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "COMMENT = " . $this->quoteValue($comment);
        } else {
            $this->tableComment = $comment;
        }
        return $this;
    }

    /**
     * Tandai tabel sebagai temporary
     */
    public function temporary(): static
    {
        $this->temporary = true;
        return $this;
    }

    // ========================================================
    //  INTERNAL HELPERS
    // ========================================================

    private function addColumnSql(string $sql): void
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD COLUMN $sql";
        } else {
            $this->columns[] = $sql;
        }
    }

    private function addIndexSql(string $sql): void
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD $sql";
        } else {
            $this->columns[] = $sql;
        }
    }

    private function finalizeForeignKey(): void
    {
        if ($this->pendingForeign) {
            $foreign = $this->pendingForeign;
            $constraintName = $foreign['name'] ?? ($this->table . '_' . $foreign['column'] . '_foreign');

            $sql = "CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$foreign['column']}`) REFERENCES `{$foreign['on']}` (`{$foreign['references']}`)";
            $sql .= " ON DELETE {$foreign['onDelete']} ON UPDATE {$foreign['onUpdate']}";

            if ($this->alterMode) {
                $this->alterStatements[] = "ADD $sql";
            } else {
                $this->foreignKeys[] = $sql;
            }
            $this->pendingForeign = null;
        }
    }

    /**
     * Modify last column/alter SQL string
     */
    private function modifyLastColumn(string $suffix): static
    {
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] .= $suffix;
            }
        } else {
            $lastIdx = count($this->columns) - 1;
            if ($lastIdx >= 0) {
                $this->columns[$lastIdx] .= $suffix;
            }
        }
        return $this;
    }

    /**
     * Replace text in last column SQL
     */
    private function replaceInLastColumn(string $search, string $replace): static
    {
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] = str_replace($search, $replace, $this->alterStatements[$lastIdx]);
            }
        } else {
            $lastIdx = count($this->columns) - 1;
            if ($lastIdx >= 0) {
                $this->columns[$lastIdx] = str_replace($search, $replace, $this->columns[$lastIdx]);
            }
        }
        return $this;
    }

    private function quoteValue($value): string
    {
        if (is_null($value))
            return 'NULL';
        if (is_bool($value))
            return $value ? '1' : '0';
        if (is_int($value) || is_float($value))
            return (string) $value;
        return "'" . addslashes((string) $value) . "'";
    }

    // ========================================================
    //  PRIMARY KEY COLUMN TYPES
    // ========================================================

    /**
     * Auto-incrementing UNSIGNED INT primary key
     */
    public function id(string $column = 'id'): static
    {
        return $this->increments($column);
    }

    /**
     * Auto-incrementing UNSIGNED INT
     */
    public function increments(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` INT UNSIGNED AUTO_INCREMENT");
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    /**
     * Auto-incrementing UNSIGNED TINYINT
     */
    public function tinyIncrements(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TINYINT UNSIGNED AUTO_INCREMENT");
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    /**
     * Auto-incrementing UNSIGNED SMALLINT
     */
    public function smallIncrements(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` SMALLINT UNSIGNED AUTO_INCREMENT");
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    /**
     * Auto-incrementing UNSIGNED MEDIUMINT
     */
    public function mediumIncrements(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` MEDIUMINT UNSIGNED AUTO_INCREMENT");
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    /**
     * Auto-incrementing UNSIGNED BIGINT primary key
     */
    public function bigIncrements(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` BIGINT UNSIGNED AUTO_INCREMENT");
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    // ========================================================
    //  INTEGER TYPES
    // ========================================================

    public function tinyInteger(string $column, bool $unsigned = false): static
    {
        $this->lastAddedColumn = $column;
        $u = $unsigned ? ' UNSIGNED' : '';
        $this->addColumnSql("`$column` TINYINT{$u}");
        return $this;
    }

    public function unsignedTinyInteger(string $column): static
    {
        return $this->tinyInteger($column, true);
    }

    public function smallInteger(string $column, bool $unsigned = false): static
    {
        $this->lastAddedColumn = $column;
        $u = $unsigned ? ' UNSIGNED' : '';
        $this->addColumnSql("`$column` SMALLINT{$u}");
        return $this;
    }

    public function unsignedSmallInteger(string $column): static
    {
        return $this->smallInteger($column, true);
    }

    public function mediumInteger(string $column, bool $unsigned = false): static
    {
        $this->lastAddedColumn = $column;
        $u = $unsigned ? ' UNSIGNED' : '';
        $this->addColumnSql("`$column` MEDIUMINT{$u}");
        return $this;
    }

    public function unsignedMediumInteger(string $column): static
    {
        return $this->mediumInteger($column, true);
    }

    public function integer(string $column, bool $unsigned = false): static
    {
        $this->lastAddedColumn = $column;
        $u = $unsigned ? ' UNSIGNED' : '';
        $this->addColumnSql("`$column` INT{$u}");
        return $this;
    }

    public function unsignedInteger(string $column): static
    {
        return $this->integer($column, true);
    }

    public function bigInteger(string $column, bool $unsigned = false): static
    {
        $this->lastAddedColumn = $column;
        $u = $unsigned ? ' UNSIGNED' : '';
        $this->addColumnSql("`$column` BIGINT{$u}");
        return $this;
    }

    public function unsignedBigInteger(string $column): static
    {
        return $this->bigInteger($column, true);
    }

    // ========================================================
    //  DECIMAL / FLOAT TYPES
    // ========================================================

    public function decimal(string $column, int $total = 8, int $places = 2): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DECIMAL($total,$places)");
        return $this;
    }

    public function unsignedDecimal(string $column, int $total = 8, int $places = 2): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DECIMAL($total,$places) UNSIGNED");
        return $this;
    }

    public function float(string $column, int $precision = 53): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` FLOAT($precision)");
        return $this;
    }

    public function double(string $column, ?int $total = null, ?int $places = null): static
    {
        $this->lastAddedColumn = $column;
        if ($total !== null && $places !== null) {
            $this->addColumnSql("`$column` DOUBLE($total,$places)");
        } else {
            $this->addColumnSql("`$column` DOUBLE");
        }
        return $this;
    }

    // ========================================================
    //  STRING TYPES
    // ========================================================

    public function char(string $column, int $length = 255): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR($length)");
        return $this;
    }

    public function string(string $column, int $length = 255): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` VARCHAR($length)");
        return $this;
    }

    public function tinyText(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TINYTEXT");
        return $this;
    }

    public function text(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TEXT");
        return $this;
    }

    public function mediumText(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` MEDIUMTEXT");
        return $this;
    }

    public function longText(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` LONGTEXT");
        return $this;
    }

    // ========================================================
    //  BINARY TYPES
    // ========================================================

    public function binary(string $column, int $length = 255): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` VARBINARY($length)");
        return $this;
    }

    public function blob(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` BLOB");
        return $this;
    }

    public function mediumBlob(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` MEDIUMBLOB");
        return $this;
    }

    public function longBlob(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` LONGBLOB");
        return $this;
    }

    // ========================================================
    //  BOOLEAN
    // ========================================================

    public function boolean(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TINYINT(1)");
        return $this;
    }

    // ========================================================
    //  DATE & TIME TYPES
    // ========================================================

    public function date(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DATE");
        return $this;
    }

    public function datetime(string $column, int $precision = 0): static
    {
        $this->lastAddedColumn = $column;
        $type = $precision > 0 ? "DATETIME($precision)" : 'DATETIME';
        $this->addColumnSql("`$column` $type");
        return $this;
    }

    public function datetimeTz(string $column, int $precision = 0): static
    {
        // MySQL tidak punya timezone-aware datetime, tapi kita siapkan method untuk kompatibilitas
        return $this->datetime($column, $precision);
    }

    public function time(string $column, int $precision = 0): static
    {
        $this->lastAddedColumn = $column;
        $type = $precision > 0 ? "TIME($precision)" : 'TIME';
        $this->addColumnSql("`$column` $type");
        return $this;
    }

    public function timeTz(string $column, int $precision = 0): static
    {
        return $this->time($column, $precision);
    }

    public function timestamp(string $column, int $precision = 0): static
    {
        $this->lastAddedColumn = $column;
        $type = $precision > 0 ? "TIMESTAMP($precision)" : 'TIMESTAMP';
        $this->addColumnSql("`$column` $type DEFAULT CURRENT_TIMESTAMP");
        return $this;
    }

    public function timestampTz(string $column, int $precision = 0): static
    {
        return $this->timestamp($column, $precision);
    }

    public function year(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` YEAR");
        return $this;
    }

    /**
     * Tambah created_at & updated_at
     */
    public function timestamps(int $precision = 0): static
    {
        $this->timestamp('created_at', $precision);
        $type = $precision > 0 ? "TIMESTAMP($precision)" : 'TIMESTAMP';
        $this->lastAddedColumn = 'updated_at';
        $this->addColumnSql("`updated_at` $type NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        return $this;
    }

    /**
     * Timestamps yang nullable
     */
    public function nullableTimestamps(int $precision = 0): static
    {
        $type = $precision > 0 ? "TIMESTAMP($precision)" : 'TIMESTAMP';
        $this->lastAddedColumn = 'created_at';
        $this->addColumnSql("`created_at` $type NULL");
        $this->lastAddedColumn = 'updated_at';
        $this->addColumnSql("`updated_at` $type NULL");
        return $this;
    }

    /**
     * Timestamps dengan timezone (alias)
     */
    public function timestampsTz(int $precision = 0): static
    {
        return $this->timestamps($precision);
    }

    // ========================================================
    //  SPECIAL TYPES
    // ========================================================

    public function uuid(string $column = 'uuid'): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR(36)");
        return $this;
    }

    /**
     * ULID column (26 chars)
     */
    public function ulid(string $column = 'ulid'): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR(26)");
        return $this;
    }

    public function json(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` JSON");
        return $this;
    }

    public function jsonb(string $column): static
    {
        // MySQL JSON is equivalent to PostgreSQL JSONB in practice
        return $this->json($column);
    }

    public function enum(string $column, array $allowedValues): static
    {
        $this->lastAddedColumn = $column;
        $values = implode("','", array_map('addslashes', $allowedValues));
        $this->addColumnSql("`$column` ENUM('$values')");
        return $this;
    }

    public function set(string $column, array $allowedValues): static
    {
        $this->lastAddedColumn = $column;
        $values = implode("','", array_map('addslashes', $allowedValues));
        $this->addColumnSql("`$column` SET('$values')");
        return $this;
    }

    /**
     * IP Address column (VARCHAR(45) untuk IPv4 & IPv6)
     */
    public function ipAddress(string $column = 'ip_address'): static
    {
        return $this->string($column, 45);
    }

    /**
     * MAC Address column
     */
    public function macAddress(string $column = 'mac_address'): static
    {
        return $this->string($column, 17);
    }

    /**
     * Geometry column (for GIS data)
     */
    public function geometry(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` GEOMETRY");
        return $this;
    }

    /**
     * Point column
     */
    public function point(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` POINT");
        return $this;
    }

    /**
     * Kolom remember_token untuk autentikasi
     */
    public function rememberToken(): static
    {
        return $this->string('remember_token', 100)->nullable();
    }

    // ========================================================
    //  POLYMORPHIC HELPERS
    // ========================================================

    /**
     * Tambah kolom polymorphic ({name}_id dan {name}_type)
     */
    public function morphs(string $name, ?string $indexName = null): static
    {
        $this->unsignedBigInteger("{$name}_id");
        $this->string("{$name}_type");
        $this->index(["{$name}_id", "{$name}_type"], $indexName ?: "{$this->table}_{$name}_id_{$name}_type_index");
        return $this;
    }

    /**
     * Polymorphic columns yang nullable
     */
    public function nullableMorphs(string $name, ?string $indexName = null): static
    {
        $this->unsignedBigInteger("{$name}_id")->nullable();
        $this->string("{$name}_type")->nullable();
        $this->index(["{$name}_id", "{$name}_type"], $indexName ?: "{$this->table}_{$name}_id_{$name}_type_index");
        return $this;
    }

    /**
     * UUID morphs (menggunakan CHAR(36) untuk id, bukan BIGINT)
     */
    public function uuidMorphs(string $name, ?string $indexName = null): static
    {
        $this->uuid("{$name}_id");
        $this->string("{$name}_type");
        $this->index(["{$name}_id", "{$name}_type"], $indexName ?: "{$this->table}_{$name}_id_{$name}_type_index");
        return $this;
    }

    /**
     * Nullable UUID morphs
     */
    public function nullableUuidMorphs(string $name, ?string $indexName = null): static
    {
        $this->uuid("{$name}_id")->nullable();
        $this->string("{$name}_type")->nullable();
        $this->index(["{$name}_id", "{$name}_type"], $indexName ?: "{$this->table}_{$name}_id_{$name}_type_index");
        return $this;
    }

    // ========================================================
    //  SOFT DELETES
    // ========================================================

    public function softDeletes(string $column = 'deleted_at', int $precision = 0): static
    {
        $type = $precision > 0 ? "TIMESTAMP($precision)" : 'TIMESTAMP';
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` $type NULL DEFAULT NULL");
        return $this;
    }

    public function softDeletesTz(string $column = 'deleted_at', int $precision = 0): static
    {
        return $this->softDeletes($column, $precision);
    }

    public function dropSoftDeletes(string $column = 'deleted_at'): static
    {
        return $this->dropColumn($column);
    }

    public function dropSoftDeletesTz(string $column = 'deleted_at'): static
    {
        return $this->dropColumn($column);
    }

    // ========================================================
    //  COLUMN MODIFIERS
    // ========================================================

    /**
     * Tandai kolom sebagai nullable
     */
    public function nullable(bool $value = true): static
    {
        if ($value) {
            $this->modifyLastColumn(' NULL');
        } else {
            $this->modifyLastColumn(' NOT NULL');
        }
        return $this;
    }

    /**
     * Set nilai default kolom
     */
    public function default($value): static
    {
        if (is_null($value)) {
            $this->modifyLastColumn(' DEFAULT NULL');
        } elseif (is_bool($value)) {
            $this->modifyLastColumn(' DEFAULT ' . ($value ? '1' : '0'));
        } elseif (is_int($value) || is_float($value)) {
            $this->modifyLastColumn(" DEFAULT $value");
        } elseif (is_string($value) && $this->isExpression($value)) {
            // Raw expressions: CURRENT_TIMESTAMP, NOW(), UUID(), dll
            $this->modifyLastColumn(" DEFAULT $value");
        } else {
            $this->modifyLastColumn(" DEFAULT " . $this->quoteValue($value));
        }
        return $this;
    }

    /**
     * Cek apakah value adalah SQL expression
     */
    private function isExpression(string $value): bool
    {
        $expressions = [
            'CURRENT_TIMESTAMP',
            'CURRENT_DATE',
            'CURRENT_TIME',
            'NOW()',
            'UUID()',
            'NULL',
        ];
        // Juga match CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        if (stripos($value, 'CURRENT_TIMESTAMP') !== false)
            return true;
        return in_array(strtoupper($value), $expressions);
    }

    /**
     * Mark as unsigned
     */
    public function unsigned(): static
    {
        // Insert UNSIGNED setelah tipe data integer
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] = preg_replace(
                    '/(TINYINT|SMALLINT|MEDIUMINT|BIGINT|INT|DECIMAL|FLOAT|DOUBLE)/',
                    '$1 UNSIGNED',
                    $this->alterStatements[$lastIdx],
                    1
                );
            }
        } else {
            $lastIdx = count($this->columns) - 1;
            if ($lastIdx >= 0) {
                $this->columns[$lastIdx] = preg_replace(
                    '/(TINYINT|SMALLINT|MEDIUMINT|BIGINT|INT|DECIMAL|FLOAT|DOUBLE)/',
                    '$1 UNSIGNED',
                    $this->columns[$lastIdx],
                    1
                );
            }
        }
        return $this;
    }

    /**
     * Mark sebagai auto increment (tanpa primary key)
     */
    public function autoIncrement(): static
    {
        $this->modifyLastColumn(' AUTO_INCREMENT');
        return $this;
    }

    /**
     * Tambah komentar pada kolom
     */
    public function columnComment(string $comment): static
    {
        $this->modifyLastColumn(" COMMENT " . $this->quoteValue($comment));
        return $this;
    }

    /**
     * Tempatkan kolom AFTER kolom lain (MySQL only)
     */
    public function after(string $column): static
    {
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] .= " AFTER `$column`";
            }
        }
        return $this;
    }

    /**
     * Tempatkan kolom di posisi pertama (MySQL only)
     */
    public function first(): static
    {
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] .= ' FIRST';
            }
        }
        return $this;
    }

    /**
     * Set charset untuk kolom terakhir
     */
    public function columnCharset(string $charset): static
    {
        $this->modifyLastColumn(" CHARACTER SET $charset");
        return $this;
    }

    /**
     * Set collation untuk kolom terakhir
     */
    public function columnCollation(string $collation): static
    {
        $this->modifyLastColumn(" COLLATE $collation");
        return $this;
    }

    /**
     * Mark invisible (MySQL 8.0.23+)
     */
    public function invisible(): static
    {
        $this->modifyLastColumn(' INVISIBLE');
        return $this;
    }

    /**
     * Set default expression menggunakan useCurrent() shorthand
     */
    public function useCurrent(): static
    {
        $this->modifyLastColumn(' DEFAULT CURRENT_TIMESTAMP');
        return $this;
    }

    /**
     * Set on update current timestamp
     */
    public function useCurrentOnUpdate(): static
    {
        $this->modifyLastColumn(' ON UPDATE CURRENT_TIMESTAMP');
        return $this;
    }

    // ========================================================
    //  GENERATED / VIRTUAL COLUMNS
    // ========================================================

    /**
     * Kolom virtual (computed, tidak disimpan)
     */
    public function virtualAs(string $expression): static
    {
        $this->modifyLastColumn(" AS ($expression) VIRTUAL");
        return $this;
    }

    /**
     * Kolom stored (computed, disimpan ke disk)
     */
    public function storedAs(string $expression): static
    {
        $this->modifyLastColumn(" AS ($expression) STORED");
        return $this;
    }

    // ========================================================
    //  ALTER TABLE OPERATIONS
    // ========================================================

    /**
     * Modify existing column (CHANGE COLUMN)
     */
    public function change(): static
    {
        if ($this->alterMode) {
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                // Ubah "ADD COLUMN" menjadi "MODIFY COLUMN"
                $this->alterStatements[$lastIdx] = str_replace(
                    'ADD COLUMN',
                    'MODIFY COLUMN',
                    $this->alterStatements[$lastIdx]
                );
            }
        }
        return $this;
    }

    /**
     * Rename kolom (MySQL 8.0+ / MariaDB)
     */
    public function renameColumn(string $old, string $new, string $typeDef = 'VARCHAR(255)'): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "CHANGE `$old` `$new` $typeDef";
        }
        return $this;
    }

    /**
     * Drop satu kolom
     */
    public function dropColumn(string|array $columns): static
    {
        if ($this->alterMode) {
            $columns = is_array($columns) ? $columns : [$columns];
            foreach ($columns as $column) {
                $this->alterStatements[] = "DROP COLUMN `$column`";
            }
        }
        return $this;
    }

    /**
     * Drop timestamps (created_at + updated_at)
     */
    public function dropTimestamps(): static
    {
        return $this->dropColumn(['created_at', 'updated_at']);
    }

    /**
     * Drop remember_token
     */
    public function dropRememberToken(): static
    {
        return $this->dropColumn('remember_token');
    }

    /**
     * Drop kolom morphs
     */
    public function dropMorphs(string $name): static
    {
        return $this->dropColumn(["{$name}_id", "{$name}_type"]);
    }

    // ========================================================
    //  INDEXES
    // ========================================================

    /**
     * Primary key tunggal atau composite
     */
    public function primary(string|array $columns, ?string $name = null): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columnList = '`' . implode('`, `', $columns) . '`';
        $name = $name ? " `$name`" : '';

        if ($this->alterMode) {
            $this->alterStatements[] = "ADD PRIMARY KEY{$name} ($columnList)";
        } else {
            $this->primaryKey = $columnList;
        }
        return $this;
    }

    /**
     * Composite primary key (alias)
     */
    public function compositePrimaryKey(array $columns): static
    {
        return $this->primary($columns);
    }

    /**
     * Unique index
     */
    public function unique(string|array|null $columns = null, ?string $name = null): static
    {
        if ($columns === null) {
            $columns = [$this->lastAddedColumn];
        }
        $columns = is_array($columns) ? $columns : [$columns];
        $columnList = '`' . implode('`, `', $columns) . '`';
        $name = $name ?: $this->table . '_' . implode('_', $columns) . '_unique';
        $this->addIndexSql("UNIQUE KEY `$name` ($columnList)");
        return $this;
    }

    /**
     * Regular index (tunggal atau composite)
     */
    public function index(string|array|null $columns = null, ?string $name = null): static
    {
        if ($columns === null) {
            $columns = [$this->lastAddedColumn];
        }
        $columns = is_array($columns) ? $columns : [$columns];
        $columnList = '`' . implode('`, `', $columns) . '`';
        $name = $name ?: 'idx_' . $this->table . '_' . implode('_', $columns);
        $this->addIndexSql("INDEX `$name` ($columnList)");
        return $this;
    }

    /**
     * Fulltext index
     */
    public function fullText(string|array $columns, ?string $indexName = null): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $cols = '`' . implode('`, `', $columns) . '`';
        $indexName = $indexName ?: 'ft_' . $this->table . '_' . implode('_', $columns);
        $this->addIndexSql("FULLTEXT KEY `$indexName` ($cols)");
        return $this;
    }

    /**
     * Spatial index
     */
    public function spatialIndex(string|array $columns, ?string $name = null): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columnList = '`' . implode('`, `', $columns) . '`';
        $name = $name ?: 'spatial_' . $this->table . '_' . implode('_', $columns);
        $this->addIndexSql("SPATIAL INDEX `$name` ($columnList)");
        return $this;
    }

    /**
     * Raw index dengan custom expression
     */
    public function rawIndex(string $expression, string $name): static
    {
        $this->addIndexSql("INDEX `$name` ($expression)");
        return $this;
    }

    // ========================================================
    //  DROP INDEX OPERATIONS
    // ========================================================

    public function dropIndex(string|array $columns): static
    {
        if ($this->alterMode) {
            if (is_string($columns) && (str_contains($columns, 'idx_') || str_contains($columns, '_index'))) {
                $this->alterStatements[] = "DROP INDEX `$columns`";
            } else {
                $columns = is_array($columns) ? $columns : [$columns];
                $name = 'idx_' . $this->table . '_' . implode('_', $columns);
                $this->alterStatements[] = "DROP INDEX `$name`";
            }
        }
        return $this;
    }

    public function dropUnique(string|array $columns): static
    {
        if ($this->alterMode) {
            if (is_string($columns) && str_contains($columns, '_unique')) {
                $this->alterStatements[] = "DROP INDEX `$columns`";
            } else {
                $columns = is_array($columns) ? $columns : [$columns];
                $name = $this->table . '_' . implode('_', $columns) . '_unique';
                $this->alterStatements[] = "DROP INDEX `$name`";
            }
        }
        return $this;
    }

    public function dropPrimary(?string $name = null): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = 'DROP PRIMARY KEY';
        }
        return $this;
    }

    public function dropFullText(string|array $columns): static
    {
        if ($this->alterMode) {
            if (is_string($columns) && str_contains($columns, 'ft_')) {
                $this->alterStatements[] = "DROP INDEX `$columns`";
            } else {
                $columns = is_array($columns) ? $columns : [$columns];
                $name = 'ft_' . $this->table . '_' . implode('_', $columns);
                $this->alterStatements[] = "DROP INDEX `$name`";
            }
        }
        return $this;
    }

    public function dropSpatialIndex(string|array $columns): static
    {
        if ($this->alterMode) {
            if (is_string($columns) && str_contains($columns, 'spatial_')) {
                $this->alterStatements[] = "DROP INDEX `$columns`";
            } else {
                $columns = is_array($columns) ? $columns : [$columns];
                $name = 'spatial_' . $this->table . '_' . implode('_', $columns);
                $this->alterStatements[] = "DROP INDEX `$name`";
            }
        }
        return $this;
    }

    public function renameIndex(string $from, string $to): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "RENAME INDEX `$from` TO `$to`";
        }
        return $this;
    }

    // ========================================================
    //  FOREIGN KEYS
    // ========================================================

    /**
     * Mulai foreign key definition
     */
    public function foreign(string $column, ?string $name = null): static
    {
        if ($this->pendingForeign) {
            $this->finalizeForeignKey();
        }

        $this->pendingForeign = [
            'column' => $column,
            'references' => null,
            'on' => null,
            'onDelete' => 'RESTRICT',
            'onUpdate' => 'CASCADE',
            'name' => $name,
        ];
        return $this;
    }

    public function references(string $column): static
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['references'] = $column;
        }
        return $this;
    }

    public function on(string $table): static
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['on'] = $table;
        }
        return $this;
    }

    public function onDelete(string $action): static
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['onDelete'] = strtoupper($action);
        }
        return $this;
    }

    public function onUpdate(string $action): static
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['onUpdate'] = strtoupper($action);
        }
        return $this;
    }

    /**
     * Buat kolom FK bertipe BIGINT UNSIGNED
     */
    public function foreignId(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` BIGINT UNSIGNED");
        return $this;
    }

    /**
     * Buat kolom FK bertipe UUID (CHAR(36))
     */
    public function foreignUuid(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR(36)");
        return $this;
    }

    /**
     * Buat kolom FK bertipe ULID (CHAR(26))
     */
    public function foreignUlid(string $column): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR(26)");
        return $this;
    }

    /**
     * Auto-constrained FK berdasarkan konvensi nama
     */
    public function constrained(?string $table = null, string $column = 'id'): static
    {
        if (!$this->lastAddedColumn) {
            throw new \RuntimeException("constrained() harus dipanggil setelah foreignId() atau method kolom lainnya");
        }

        if ($table === null) {
            // Smart pluralization
            $table = $this->pluralize(str_replace('_id', '', $this->lastAddedColumn));
        }

        $this->foreign($this->lastAddedColumn)
            ->references($column)
            ->on($table);

        return $this;
    }

    /**
     * Pluralization yang lebih baik dari sekadar menambah 's'
     */
    private function pluralize(string $word): string
    {
        $irregulars = [
            'child' => 'children',
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'mouse' => 'mice',
            'goose' => 'geese',
            'foot' => 'feet',
            'tooth' => 'teeth',
            'ox' => 'oxen',
            'leaf' => 'leaves',
            'life' => 'lives',
            'knife' => 'knives',
            'wife' => 'wives',
            'half' => 'halves',
            'self' => 'selves',
            'calf' => 'calves',
            'shelf' => 'shelves',
            'wolf' => 'wolves',
            'quiz' => 'quizzes',
            'bus' => 'buses',
        ];

        $lower = strtolower($word);
        if (isset($irregulars[$lower])) {
            return $irregulars[$lower];
        }

        // Rules
        if (preg_match('/(s|sh|ch|x|z)$/i', $word))
            return $word . 'es';
        if (preg_match('/[^aeiou]y$/i', $word))
            return preg_replace('/y$/i', 'ies', $word);
        if (preg_match('/(f|fe)$/i', $word))
            return preg_replace('/(f|fe)$/i', 'ves', $word);
        if (preg_match('/o$/i', $word) && !preg_match('/(photo|piano|video|zoo|radio|memo|studio)$/i', $word))
            return $word . 'es';

        return $word . 's';
    }

    // FK Action Shorthands
    public function cascadeOnDelete(): static
    {
        return $this->onDelete('CASCADE');
    }
    public function restrictOnDelete(): static
    {
        return $this->onDelete('RESTRICT');
    }
    public function noActionOnDelete(): static
    {
        return $this->onDelete('NO ACTION');
    }
    public function nullOnDelete(): static
    {
        return $this->onDelete('SET NULL');
    }
    public function cascadeOnUpdate(): static
    {
        return $this->onUpdate('CASCADE');
    }
    public function restrictOnUpdate(): static
    {
        return $this->onUpdate('RESTRICT');
    }
    public function noActionOnUpdate(): static
    {
        return $this->onUpdate('NO ACTION');
    }

    /**
     * Drop foreign key constraint
     */
    public function dropForeign(string|array $columns): static
    {
        if ($this->alterMode) {
            $columns = is_array($columns) ? $columns : [$columns];
            foreach ($columns as $column) {
                if (str_contains($column, '_foreign') || str_starts_with($column, 'fk_')) {
                    $constraintName = $column;
                } else {
                    $constraintName = $this->table . '_' . $column . '_foreign';
                }
                $this->alterStatements[] = "DROP FOREIGN KEY `$constraintName`";
            }
        }
        return $this;
    }

    /**
     * Drop constrained FK + index sekaligus
     */
    public function dropConstrainedForeignId(string $column): static
    {
        $this->dropForeign($column);
        $this->dropIndex($column);
        $this->dropColumn($column);
        return $this;
    }

    // ========================================================
    //  🚀 BEYOND LARAVEL — EXTRA FEATURES
    // ========================================================

    /**
     * Raw column SQL — untuk tipe data yang belum di-abstract
     */
    public function rawColumn(string $column, string $definition): static
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` $definition");
        return $this;
    }

    /**
     * Raw SQL statement langsung ke alter statements
     */
    public function rawStatement(string $sql): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = $sql;
        } else {
            $this->columns[] = $sql;
        }
        return $this;
    }

    /**
     * Conditional column — hanya tambah jika kondisi true
     * 
     * Contoh: $table->columnIf($hasSlug, fn($t) => $t->string('slug')->unique())
     */
    public function columnIf(bool $condition, callable $callback): static
    {
        if ($condition) {
            $callback($this);
        }
        return $this;
    }

    /**
     * CHECK constraint (MySQL 8.0.16+)
     */
    public function check(string $expression, ?string $name = null): static
    {
        $name = $name ? "CONSTRAINT `$name` " : '';
        $sql = "{$name}CHECK ($expression)";
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD $sql";
        } else {
            $this->columns[] = $sql;
        }
        return $this;
    }

    /**
     * Drop CHECK constraint
     */
    public function dropCheck(string $name): static
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "DROP CHECK `$name`";
        }
        return $this;
    }

    /**
     * Tambah kolom JSON path virtual index (MySQL 8+)
     * Berguna untuk mengindex field di dalam kolom JSON
     */
    public function jsonIndex(string $jsonColumn, string $jsonPath, string $castType = 'VARCHAR(255)', ?string $indexName = null): static
    {
        $virtualColName = str_replace(['->', '.'], '_', $jsonColumn . '_' . trim($jsonPath, '$.'));
        $virtualColName = preg_replace('/[^a-zA-Z0-9_]/', '', $virtualColName);

        $indexName = $indexName ?: "idx_{$this->table}_{$virtualColName}";

        // Buat generated virtual column
        $this->rawColumn($virtualColName, "$castType AS (JSON_UNQUOTE(JSON_EXTRACT(`$jsonColumn`, '$jsonPath'))) VIRTUAL");
        $this->index($virtualColName, $indexName);

        return $this;
    }

    /**
     * Auto-create audit columns (created_by, updated_by)
     */
    public function auditColumns(): static
    {
        $this->unsignedBigInteger('created_by')->nullable();
        $this->unsignedBigInteger('updated_by')->nullable();
        return $this;
    }

    /**
     * Drop audit columns
     */
    public function dropAuditColumns(): static
    {
        return $this->dropColumn(['created_by', 'updated_by']);
    }

    /**
     * Status pattern (enum + index + default)
     */
    public function status(string $column = 'status', array $values = ['active', 'inactive'], string $defaultVal = 'active'): static
    {
        $this->enum($column, $values)->default($defaultVal)->index($column);
        return $this;
    }

    /**
     * Slug column (string + unique index)
     */
    public function slug(string $column = 'slug', int $length = 255): static
    {
        $this->string($column, $length)->unique($column);
        return $this;
    }

    /**
     * Money column (DECIMAL(19,4) — standard keuangan)
     */
    public function money(string $column, int $total = 19, int $places = 4): static
    {
        return $this->decimal($column, $total, $places);
    }

    /**
     * Percentage column (DECIMAL(5,2) — 0.00 sampai 100.00)
     */
    public function percentage(string $column): static
    {
        return $this->decimal($column, 5, 2);
    }

    // Finalize di akhir jika ada sisa
    public function __destruct()
    {
        // Finalize dilakukan oleh getForeignKeys()
    }
}
