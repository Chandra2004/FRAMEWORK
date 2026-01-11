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
        $this->addColumnSql("`$column` VARCHAR($length)");
        return $this;
    }

    public function integer($column, $unsigned = false)
    {
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
        $this->addColumnSql("`$column` TEXT");
        return $this;
    }

    public function longText($column)
    {
        $this->addColumnSql("`$column` LONGTEXT");
        return $this;
    }

    public function boolean($column)
    {
        $this->addColumnSql("`$column` TINYINT(1)");
        return $this;
    }

    public function timestamp($column)
    {
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
        $this->addColumnSql("`$column` DATE");
        return $this;
    }

    public function datetime($column)
    {
        $this->addColumnSql("`$column` DATETIME");
        return $this;
    }

    public function time($column)
    {
        $this->addColumnSql("`$column` TIME");
        return $this;
    }

    public function decimal($column, $total = 8, $places = 2)
    {
        $this->addColumnSql("`$column` DECIMAL($total,$places)");
        return $this;
    }

    public function uuid($column)
    {
        $this->addColumnSql("`$column` CHAR(36)");
        return $this;
    }

    public function json($column)
    {
        $this->addColumnSql("`$column` JSON");
        return $this;
    }

    public function enum($column, array $allowedValues)
    {
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
        // Jika dipanggil chaining (nullable()->unique()), ambil kolom terakhir
        if ($column === null) {
            // Extract nama kolom dari definition terakhir (agak tricky regex-nya)
            // Simplifikasi: user harus pass nama kolom jika standalone
            return $this;
        }

        $this->addIndexSql("UNIQUE (`$column`)");
        return $this;
    }

    public function index($column)
    {
        $this->addIndexSql("INDEX idx_$column (`$column`)");
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

    // Finalize di akhir jika ada sisa
    public function __destruct()
    {
        // Tidak bisa finalize di destruct karena object blueprint di Schema mungkin sudah selesai dipakai sebelum query dijalankan
        // Jadi finalize harus dipanggil oleh method getForeignKeys()
    }
}
