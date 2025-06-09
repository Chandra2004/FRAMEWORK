<?php
    namespace {{NAMESPACE}}\Database;

    use {{NAMESPACE}}\App\Database;

    abstract class Migration {
        protected Database $db;

        public function __construct()
        {
            $this->db = Database::getInstance();
        }

        abstract public function up();
        abstract public function down();
    }
?>
