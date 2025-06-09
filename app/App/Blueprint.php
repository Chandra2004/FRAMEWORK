<?php
    namespace {{NAMESPACE}}\App;

    class Blueprint {
        private $table;
        private $columns = [];
        private $primaryKey = null;
        private $foreignKeys = [];
        private $pendingForeign = null;

        public function __construct($table) {
            $this->table = $table;
        }

        public function id() {
            return $this->increments('id');
        }

        public function increments($column) {
            $this->columns[] = "`$column` INT UNSIGNED AUTO_INCREMENT";
            $this->primaryKey = "`$column`";
            return $this;
        }

        public function string($column, $length = 255) {
            $this->columns[] = "`$column` VARCHAR($length)";
            return $this;
        }

        public function integer($column, $unsigned = false) {
            $unsigned = $unsigned ? " UNSIGNED" : "";
            $this->columns[] = "`$column` INT$unsigned";
            return $this;
        }

        public function text($column) {
            $this->columns[] = "`$column` TEXT";
            return $this;
        }

        public function boolean($column) {
            $this->columns[] = "`$column` TINYINT(1)";
            return $this;
        }

        public function timestamp($column) {
            $this->columns[] = "`$column` TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            return $this;
        }

        public function date($column) {
            $this->columns[] = "`$column` DATE";
            return $this;
        }

        public function decimal($column, $total, $places) {
            $this->columns[] = "`$column` DECIMAL($total,$places)";
            return $this;
        }

        public function enum($column, array $allowedValues) {
            $values = implode("','", array_map('addslashes', $allowedValues));
            $this->columns[] = "`$column` ENUM('$values')";
            return $this;
        }

        public function nullable() {
            $lastIndex = count($this->columns) - 1;
            $this->columns[$lastIndex] .= " NULL";
            return $this;
        }

        public function default($value) {
            $lastIndex = count($this->columns) - 1;
            $defaultValue = is_string($value) ? "'$value'" : $value;
            $this->columns[$lastIndex] .= " DEFAULT $defaultValue";
            return $this;
        }

        public function unique() {
            $lastIndex = count($this->columns) - 1;
            preg_match('/`(.+?)`/', $this->columns[$lastIndex], $matches);
            if (!empty($matches[1])) {
                $this->columns[] = "UNIQUE (`{$matches[1]}`)";
            }
            return $this;
        }

        public function index($column) {
            $this->columns[] = "INDEX idx_$column (`$column`)";
            return $this;
        }

        public function compositePrimaryKey(array $columns) {
            $columnList = implode('`, `', $columns);
            $this->primaryKey = "`$columnList`";
            return $this;
        }

        public function timestamps() {
            $this->timestamp('created_at');
            $this->timestamp('updated_at');
            return $this;
        }

        public function getColumns() {
            return $this->columns;
        }

        public function getPrimaryKey() {
            return $this->primaryKey;
        }

        public function getForeignKeys() {
            return $this->foreignKeys;
        }

        public function unsignedInteger($column) {
            return $this->integer($column, true);
        }        

        public function foreign($column) {
            $this->pendingForeign = [
                'column' => $column,
                'references' => null,
                'on' => null,
                'onDelete' => 'RESTRICT',
                'onUpdate' => 'CASCADE',
            ];
            return $this;
        }

        public function references($column){
            if ($this->pendingForeign) {
                $this->pendingForeign['references'] = $column;
            }
            return $this;
        }

        public function on($table) {
            if ($this->pendingForeign) {
                $this->pendingForeign['on'] = $table;
            }
            return $this;
        }

        public function onDelete($action) {
            if ($this->pendingForeign) {
                $this->pendingForeign['onDelete'] = strtoupper($action);
            }
            return $this;
        }

        public function onUpdate($action) {
            if ($this->pendingForeign) {
                $this->pendingForeign['onUpdate'] = strtoupper($action);

                // Baru setelah onUpdate selesai, simpan ke foreignKeys
                $foreign = $this->pendingForeign;
                $this->foreignKeys[] = "FOREIGN KEY (`{$foreign['column']}`) REFERENCES `{$foreign['on']}` (`{$foreign['references']}`) ON DELETE {$foreign['onDelete']} ON UPDATE {$foreign['onUpdate']}";
                $this->pendingForeign = null;
            }
            return $this;
        }
    }
?>
