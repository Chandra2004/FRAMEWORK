<?php

namespace TheFramework\App;

class Blueprint
{
    private $table;
    private $columns = [];
    private $primaryKey = null;
    private $foreignKeys = [];
    private $pendingForeign = null;
    private $alterMode = false;
    private $alterStatements = [];
    private $lastAddedColumn = null;

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function setAlterMode()
    {
        $this->alterMode = true;
    }

    public function getAlterStatements()
    {
        return $this->alterStatements;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getForeignKeys()
    {
        // Process pending foreign key if any
        if ($this->pendingForeign) {
            $this->finalizeForeignKey();
        }
        return $this->foreignKeys;
    }

    // --- INTERNAL HELPERS ---

    private function addColumnSql($sql)
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD COLUMN $sql";
        } else {
            $this->columns[] = $sql;
        }
    }

    private function addIndexSql($sql)
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD $sql";
        } else {
            $this->columns[] = $sql;
        }
    }

    private function finalizeForeignKey()
    {
        if ($this->pendingForeign) {
            $foreign = $this->pendingForeign;
            $sql = "FOREIGN KEY (`{$foreign['column']}`) REFERENCES `{$foreign['on']}` (`{$foreign['references']}`) ON DELETE {$foreign['onDelete']} ON UPDATE {$foreign['onUpdate']}";

            if ($this->alterMode) {
                // Untuk alter table, syntaxnya ADD CONSTRAINT or just ADD FOREIGN KEY
                $this->alterStatements[] = "ADD $sql";
            } else {
                $this->foreignKeys[] = $sql;
            }
            $this->pendingForeign = null;
        }
    }

    // --- ALTERATION METHODS ---

    public function renameColumn($old, $new, $typeDef = "VARCHAR(255)")
    {
        if ($this->alterMode) {
            // MySQL CHANGE COLUMN butuh definisi tipe data ulang
            $this->alterStatements[] = "CHANGE `$old` `$new` $typeDef";
        }
        return $this;
    }

    public function dropColumn($column)
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "DROP COLUMN `$column`";
        }
        return $this;
    }

    public function dropIndex($indexName)
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "DROP INDEX `$indexName`";
        }
        return $this;
    }

    // --- COLUMN TYPES ---

    public function id()
    {
        return $this->increments('id');
    }

    public function increments($column)
    {
        $this->lastAddedColumn = $column;
        $sql = "`$column` INT UNSIGNED AUTO_INCREMENT";
        $this->addColumnSql($sql);
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            // Kalau alter table add primary key
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    public function bigIncrements($column)
    {
        $this->lastAddedColumn = $column;
        $sql = "`$column` BIGINT UNSIGNED AUTO_INCREMENT";
        $this->addColumnSql($sql);
        if (!$this->alterMode) {
            $this->primaryKey = "`$column`";
        } else {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$column`)";
        }
        return $this;
    }

    public function string($column, $length = 255)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` VARCHAR($length)");
        return $this;
    }

    public function integer($column, $unsigned = false)
    {
        $this->lastAddedColumn = $column;
        $unsigned = $unsigned ? " UNSIGNED" : "";
        $this->addColumnSql("`$column` INT$unsigned");
        return $this;
    }

    public function unsignedInteger($column)
    {
        return $this->integer($column, true);
    }

    public function text($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TEXT");
        return $this;
    }

    public function longText($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` LONGTEXT");
        return $this;
    }

    public function boolean($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TINYINT(1)");
        return $this;
    }

    public function timestamp($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        return $this;
    }

    public function timestamps()
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at')->nullable()->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        return $this;
    }

    public function date($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DATE");
        return $this;
    }

    public function datetime($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DATETIME");
        return $this;
    }

    public function time($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` TIME");
        return $this;
    }

    public function decimal($column, $total = 8, $places = 2)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` DECIMAL($total,$places)");
        return $this;
    }

    public function uuid($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` CHAR(36)");
        return $this;
    }

    public function json($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` JSON");
        return $this;
    }

    public function enum($column, array $allowedValues)
    {
        $this->lastAddedColumn = $column;
        $values = implode("','", array_map('addslashes', $allowedValues));
        $this->addColumnSql("`$column` ENUM('$values')");
        return $this;
    }

    // --- MODIFIERS ---

    public function nullable()
    {
        $this->modifyLastColumn(" NULL");
        return $this;
    }

    public function default($value)
    {
        $defaultValue = is_string($value) && strtoupper($value) !== 'CURRENT_TIMESTAMP' && strpos($value, 'CURRENT_TIMESTAMP') === false
            ? "'$value'"
            : $value;
        $this->modifyLastColumn(" DEFAULT $defaultValue");
        return $this;
    }

    public function unsigned()
    {
        if ($this->alterMode) {
            // Agak kompleks memodifikasi string SQL terakhir di alterStatements, 
            // tapi asumsi method chaining dipanggil urut.
            $lastIdx = count($this->alterStatements) - 1;
            if ($lastIdx >= 0) {
                $this->alterStatements[$lastIdx] = str_replace('INT', 'INT UNSIGNED', $this->alterStatements[$lastIdx]);
            }
        } else {
            $lastIdx = count($this->columns) - 1;
            if ($lastIdx >= 0) {
                $this->columns[$lastIdx] = str_replace('INT', 'INT UNSIGNED', $this->columns[$lastIdx]);
            }
        }
        return $this;
    }

    // Helper untuk modify string kolom terakhir
    private function modifyLastColumn($suffix)
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
    }

    // --- INDEXES ---

    public function unique($column = null)
    {
        $column = $column ?: $this->lastAddedColumn;
        if ($column) {
            $this->addIndexSql("UNIQUE (`$column`)");
        }
        return $this;
    }

    public function index($column = null)
    {
        $column = $column ?: $this->lastAddedColumn;
        if ($column) {
            $this->addIndexSql("INDEX idx_$column (`$column`)");
        }
        return $this;
    }

    public function fullText(array $columns, $indexName = null)
    {
        $cols = implode("`, `", $columns);
        if (!$indexName) {
            $indexName = "ft_" . implode("_", $columns);
        }
        $this->addIndexSql("FULLTEXT KEY `$indexName` (`$cols`)");
        return $this;
    }

    public function compositePrimaryKey(array $columns)
    {
        $columnList = implode('`, `', $columns);
        if ($this->alterMode) {
            $this->alterStatements[] = "ADD PRIMARY KEY (`$columnList`)";
        } else {
            $this->primaryKey = "`$columnList`";
        }
        return $this;
    }

    // --- FOREIGN KEYS ---

    public function foreign($column)
    {
        // Jika ada pending foreign key sebelumnya, finalize dulu
        if ($this->pendingForeign) {
            $this->finalizeForeignKey();
        }

        $this->pendingForeign = [
            'column' => $column,
            'references' => null,
            'on' => null,
            'onDelete' => 'RESTRICT',
            'onUpdate' => 'CASCADE',
        ];
        return $this;
    }

    public function references($column)
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['references'] = $column;
        }
        return $this;
    }

    public function on($table)
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['on'] = $table;
        }
        return $this;
    }

    public function onDelete($action)
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['onDelete'] = strtoupper($action);
        }
        return $this;
    }

    public function onUpdate($action)
    {
        if ($this->pendingForeign) {
            $this->pendingForeign['onUpdate'] = strtoupper($action);
        }
        return $this;
    }

    /**
     * Helper method untuk membuat foreign key column dengan tipe BIGINT UNSIGNED
     * Mirip dengan Laravel's foreignId()
     * 
     * @param string $column Nama kolom (contoh: 'user_id')
     * @return $this
     */
    public function foreignId($column)
    {
        $this->lastAddedColumn = $column;
        $this->addColumnSql("`$column` BIGINT UNSIGNED");
        return $this;
    }

    /**
     * Helper method untuk membuat foreign key dengan konvensi penamaan otomatis
     * Contoh: $table->foreignId('user_id')->constrained() akan reference ke tabel 'users'
     * 
     * @param string|null $table Nama tabel yang direferensikan (opsional, default: auto-detect dari nama kolom)
     * @param string $column Nama kolom yang direferensikan (default: 'id')
     * @return $this
     */
    public function constrained($table = null, $column = 'id')
    {
        if (!$this->lastAddedColumn) {
            throw new \RuntimeException("constrained() harus dipanggil setelah foreignId() atau method kolom lainnya");
        }

        // Auto-detect table name dari column name (contoh: user_id -> users)
        if ($table === null) {
            // Hapus '_id' dari nama kolom
            $singularTable = str_replace('_id', '', $this->lastAddedColumn);

            // Simple pluralization (tambah 's')
            // Untuk pluralization lebih kompleks, bisa tambahkan library inflector
            $table = $singularTable . 's';
        }

        // Buat foreign key constraint
        $this->foreign($this->lastAddedColumn)
            ->references($column)
            ->on($table);

        return $this;
    }

    /**
     * Shorthand untuk onDelete('CASCADE')
     */
    public function cascadeOnDelete()
    {
        return $this->onDelete('CASCADE');
    }

    /**
     * Shorthand untuk onDelete('RESTRICT')
     */
    public function restrictOnDelete()
    {
        return $this->onDelete('RESTRICT');
    }

    /**
     * Shorthand untuk onDelete('SET NULL')
     */
    public function nullOnDelete()
    {
        return $this->onDelete('SET NULL');
    }

    /**
     * Shorthand untuk onUpdate('CASCADE')
     */
    public function cascadeOnUpdate()
    {
        return $this->onUpdate('CASCADE');
    }

    /**
     * Drop foreign key constraint
     * 
     * @param string|array $columns Nama kolom atau array nama kolom yang memiliki foreign key
     * @return $this
     */
    public function dropForeign($columns)
    {
        if ($this->alterMode) {
            // Convert to array jika string
            $columns = is_array($columns) ? $columns : [$columns];

            foreach ($columns as $column) {
                // MySQL menggunakan nama foreign key untuk drop
                // Konvensi nama: {table}_{column}_foreign
                // Atau user bisa pass nama constraint langsung

                // Jika sudah format constraint name (mengandung '_foreign' atau 'fk_')
                if (strpos($column, '_foreign') !== false || strpos($column, 'fk_') === 0) {
                    $constraintName = $column;
                } else {
                    // Generate constraint name berdasarkan konvensi
                    $constraintName = $this->table . '_' . $column . '_foreign';
                }

                $this->alterStatements[] = "DROP FOREIGN KEY `$constraintName`";
            }
        }
        return $this;
    }

    // Finalize di akhir jika ada sisa
    public function __destruct()
    {
        // Tidak bisa finalize di destruct karena object blueprint di Schema mungkin sudah selesai dipakai sebelum query dijalankan
        // Jadi finalize harus dipanggil oleh method getForeignKeys()
    }
}
