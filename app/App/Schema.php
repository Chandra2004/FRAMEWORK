<?php

namespace TheFramework\App;

use Closure;
use TheFramework\App\Database;
use TheFramework\App\Blueprint;

class Schema
{
    public static function table($table, Closure $callback)
    {
        $db = Database::getInstance();
        $blueprint = new Blueprint($table);
        $blueprint->setAlterMode();
        $callback($blueprint);

        $statements = $blueprint->getAlterStatements();
        foreach ($statements as $sql) {
            $db->query("ALTER TABLE `$table` $sql;");
            $db->execute();
        }
    }

    public static function create($table, Closure $callback)
    {
        $db = Database::getInstance();
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (";
        $sql .= implode(", ", $blueprint->getColumns());

        if ($blueprint->getPrimaryKey()) {
            $sql .= ", PRIMARY KEY (" . $blueprint->getPrimaryKey() . ")";
        }

        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $sql .= ", $foreignKey";
        }

        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $db->query($sql);
        $db->execute();
    }

    public static function dropIfExists($table)
    {
        $db = Database::getInstance();
        $sql = "DROP TABLE IF EXISTS `$table`;";
        $db->query($sql);
        $db->execute();
    }

    public static function insert(string $table, array $rows)
    {
        if (empty($rows))
            return;

        $db = Database::getInstance();
        $columns = array_keys($rows[0]);
        $columnList = "`" . implode("`, `", $columns) . "`";

        $values = [];
        foreach ($rows as $row) {
            $escaped = array_map(function ($value) use ($db) {
                return $db->quote($value); // Pastikan Database class punya method quote()
            }, $row);
            $values[] = "(" . implode(", ", $escaped) . ")";
        }

        $sql = "INSERT INTO `$table` ($columnList) VALUES " . implode(", ", $values) . ";";

        $db->query($sql);
        $db->execute();
    }
}
