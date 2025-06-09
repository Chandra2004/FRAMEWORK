<?php
    namespace {{NAMESPACE}}\App;

    use Closure;
    use {{NAMESPACE}}\App\Database;
    use {{NAMESPACE}}\App\Blueprint;

    class Schema {
        public static function create($table, Closure $callback) {
            $db = Database::getInstance();
            $blueprint = new Blueprint($table);
            $callback($blueprint);

            // Debug: Cetak isi blueprint sebelum membuat tabel
            output('info', "Membuat tabel '$table' dengan kolom: " . json_encode($blueprint->getColumns()));
            output('info', "Primary Key: " . $blueprint->getPrimaryKey());
            output('info', "Foreign Keys: " . json_encode($blueprint->getForeignKeys()));

            $sql = "CREATE TABLE IF NOT EXISTS `$table` (";
            $sql .= implode(", ", $blueprint->getColumns());

            if ($blueprint->getPrimaryKey()) {
                $sql .= ", PRIMARY KEY (" . $blueprint->getPrimaryKey() . ")";
            }

            foreach ($blueprint->getForeignKeys() as $foreignKey) {
                $sql .= ", $foreignKey";
            }

            $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            output('info', "SQL yang dijalankan: $sql");
            $db->query($sql);
            $db->execute();
        }

        public static function dropIfExists($table) {
            $db = Database::getInstance();
            $sql = "DROP TABLE IF EXISTS `$table`;";
            $db->query($sql);
            $db->execute();
        }
    }
?>
