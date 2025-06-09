<?php
    namespace {{NAMESPACE}}\Models\Seeders;

    use {{NAMESPACE}}\App\Database;

    class User {
        protected static Database $db;

        public static function create(array $data) {
            self::$db = Database::getInstance();
            $columns = implode(", ", array_keys($data));
            $placeholders = ":" . implode(", :", array_keys($data));

            $sql = "INSERT INTO users ($columns) VALUES ($placeholders)";
            self::$db->query($sql);

            foreach ($data as $key => $value) {
                self::$db->bind(":$key", $value);
            }

            return self::$db->execute();
        }
    }
?>
