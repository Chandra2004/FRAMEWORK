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

    public function setAlterMode()
    {
        $this->alterMode = true;
    }

    public function renameColumn($old, $new)
    {
        if ($this->alterMode) {
            $this->alterStatements[] = "CHANGE `$old` `$new` VARCHAR(255)";
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

    public function getAlterStatements()
    {
        return $this->alterStatements;
    }

    // contoh tipe baru
    public function bigIncrements($column)
    {
        $this->columns[] = "`$column` BIGINT UNSIGNED AUTO_INCREMENT";
        $this->primaryKey = "`$column`";
        return $this;
    }

    public function uuid($column)
    {
        $this->columns[] = "`$column` CHAR(36)";
        return $this;
    }

    public function time($column)
    {
        $this->columns[] = "`$column` TIME";
        return $this;
    }

    public function json($column)
    {
        $this->columns[] = "`$column` JSON";
        return $this;
    }

    public function softDeletes()
    {
        $this->timestamp('deleted_at')->nullable();
        return $this;
    }

    public function __construct($table)
    {
        $this->table = $table;
    }

    public function id()
    {
        return $this->increments('id');
    }

    public function increments($column)
    {
        $this->columns[] = "`$column` INT UNSIGNED AUTO_INCREMENT";
        $this->primaryKey = "`$column`";
        return $this;
    }

    public function string($column, $length = 255)
    {
        $this->columns[] = "`$column` VARCHAR($length)";
        return $this;
    }

    public function integer($column, $unsigned = false)
    {
        $unsigned = $unsigned ? " UNSIGNED" : "";
        $this->columns[] = "`$column` INT$unsigned";
        return $this;
    }

    public function text($column)
    {
        $this->columns[] = "`$column` TEXT";
        return $this;
    }

    public function longText($column)
    {
        $this->columns[] = "`$column` LONGTEXT";
        return $this;
    }

    public function boolean($column)
    {
        $this->columns[] = "`$column` TINYINT(1)";
        return $this;
    }

    public function timestamp($column)
    {
        $this->columns[] = "`$column` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        return $this;
    }

    public function date($column)
    {
        $this->columns[] = "`$column` DATE";
        return $this;
    }

    public function datetime($column)
    {
        $this->columns[] = "`$column` DATETIME";
        return $this;
    }


    public function decimal($column, $total, $places)
    {
        $this->columns[] = "`$column` DECIMAL($total,$places)";
        return $this;
    }

    public function enum($column, array $allowedValues)
    {
        $values = implode("','", array_map('addslashes', $allowedValues));
        $this->columns[] = "`$column` ENUM('$values')";
        return $this;
    }

    public function nullable()
    {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " NULL";
        return $this;
    }

    public function default($value)
    {
        $lastIndex = count($this->columns) - 1;
        $defaultValue = is_string($value) ? "'$value'" : $value;
        $this->columns[$lastIndex] .= " DEFAULT $defaultValue";
        return $this;
    }

    public function unique()
    {
        $lastIndex = count($this->columns) - 1;
        preg_match('/`(.+?)`/', $this->columns[$lastIndex], $matches);
        if (!empty($matches[1])) {
            $this->columns[] = "UNIQUE (`{$matches[1]}`)";
        }
        return $this;
    }

    public function index($column)
    {
        $this->columns[] = "INDEX idx_$column (`$column`)";
        return $this;
    }

    public function compositePrimaryKey(array $columns)
    {
        $columnList = implode('`, `', $columns);
        $this->primaryKey = "`$columnList`";
        return $this;
    }

    public function timestamps()
    {
        $this->timestamp('created_at');
        $this->timestamp('updated_at');
        return $this;
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
        if ($this->pendingForeign) {
            $foreign = $this->pendingForeign;
            $this->foreignKeys[] = "FOREIGN KEY (`{$foreign['column']}`) REFERENCES `{$foreign['on']}` (`{$foreign['references']}`) ON DELETE {$foreign['onDelete']} ON UPDATE {$foreign['onUpdate']}";
            $this->pendingForeign = null;
        }
        return $this->foreignKeys;
    }

    public function unsignedInteger($column)
    {
        return $this->integer($column, true);
    }

    public function unsigned()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0 && strpos($this->columns[$lastIndex], 'INT') !== false) {
            $this->columns[$lastIndex] = str_replace('INT', 'INT UNSIGNED', $this->columns[$lastIndex]);
        }
        return $this;
    }

    public function foreign($column)
    {
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

    public function fullText(array $columns, $indexName = null)
    {
        $cols = implode("`, `", $columns);
        if (!$indexName) {
            $indexName = "ft_" . implode("_", $columns);
        }
        $this->columns[] = "FULLTEXT KEY `$indexName` (`$cols`)";
        return $this;
    }
}
