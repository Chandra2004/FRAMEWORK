<?php

namespace TheFramework\App\Database;

/**
 * Blueprint for creating or modifying database tables.
 */
class Blueprint
{
    protected string $table;
    protected array $columns = [];
    protected array $commands = [];
    protected string $engine = 'InnoDB';
    protected string $charset = 'utf8mb4';
    protected string $collation = 'utf8mb4_unicode_ci';

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(string $column = 'id'): self
    {
        return $this->bigIncrements($column);
    }

    public function increments(string $column): self
    {
        return $this->addColumn('integer', $column, ['auto_increment' => true, 'primary' => true, 'unsigned' => true]);
    }

    public function bigIncrements(string $column): self
    {
        return $this->addColumn('bigint', $column, ['auto_increment' => true, 'primary' => true, 'unsigned' => true]);
    }

    public function string(string $column, int $length = 255): self
    {
        return $this->addColumn('varchar', $column, ['length' => $length]);
    }

    public function text(string $column): self
    {
        return $this->addColumn('text', $column);
    }

    public function longText(string $column): self
    {
        return $this->addColumn('longtext', $column);
    }

    public function integer(string $column): self
    {
        return $this->addColumn('int', $column);
    }

    public function bigInteger(string $column): self
    {
        return $this->addColumn('bigint', $column);
    }

    public function unsignedInteger(string $column): self
    {
        return $this->addColumn('int', $column, ['unsigned' => true]);
    }

    public function unsignedBigInteger(string $column): self
    {
        return $this->addColumn('bigint', $column, ['unsigned' => true]);
    }

    public function boolean(string $column): self
    {
        return $this->addColumn('tinyint', $column, ['length' => 1]);
    }

    public function decimal(string $column, int $total = 8, int $places = 2): self
    {
        return $this->addColumn('decimal', $column, ['total' => $total, 'places' => $places]);
    }

    public function date(string $column): self
    {
        return $this->addColumn('date', $column);
    }

    public function dateTime(string $column): self
    {
        return $this->addColumn('datetime', $column);
    }

    public function timestamp(string $column): self
    {
        return $this->addColumn('timestamp', $column);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function softDeletes(): void
    {
        $this->timestamp('deleted_at')->nullable();
    }

    public function rememberToken(): void
    {
        $this->string('remember_token', 100)->nullable();
    }

    public function nullable(): self
    {
        $last = array_key_last($this->columns);
        $this->columns[$last]['nullable'] = true;
        return $this;
    }

    public function default($value): self
    {
        $last = array_key_last($this->columns);
        $this->columns[$last]['default'] = $value;
        return $this;
    }

    public function unique(?string $name = null): self
    {
        $last = array_key_last($this->columns);
        $this->commands[] = [
            'type' => 'unique',
            'columns' => [$this->columns[$last]['name']],
            'name' => $name
        ];
        return $this;
    }

    public function index(?string $name = null): self
    {
        $last = array_key_last($this->columns);
        $this->commands[] = [
            'type' => 'index',
            'columns' => [$this->columns[$last]['name']],
            'name' => $name
        ];
        return $this;
    }

    public function foreign(string $column): self
    {
        $this->commands[] = [
            'type' => 'foreign',
            'column' => $column
        ];
        return $this;
    }

    public function references(string $column): self
    {
        $last = array_key_last($this->commands);
        $this->commands[$last]['references'] = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $last = array_key_last($this->commands);
        $this->commands[$last]['on'] = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $last = array_key_last($this->commands);
        $this->commands[$last]['onDelete'] = $action;
        return $this;
    }

    protected function addColumn(string $type, string $name, array $parameters = []): self
    {
        $this->columns[] = array_merge(['type' => $type, 'name' => $name], $parameters);
        return $this;
    }

    public function toSql(): string
    {
        $sql = "CREATE TABLE `{$this->table}` (\n";
        
        $lines = [];
        foreach ($this->columns as $column) {
            $lines[] = "  " . $this->buildColumnSql($column);
        }

        foreach ($this->commands as $command) {
            $line = $this->buildCommandSql($command);
            if ($line) $lines[] = "  " . $line;
        }

        $sql .= implode(",\n", $lines);
        $sql .= "\n) ENGINE={$this->engine} DEFAULT CHARSET={$this->charset} COLLATE={$this->collation};";

        return $sql;
    }

    protected function buildColumnSql(array $column): string
    {
        $sql = "`{$column['name']}` {$column['type']}";

        if (isset($column['length'])) {
            $sql .= "({$column['length']})";
        } elseif (isset($column['total'])) {
            $sql .= "({$column['total']},{$column['places']})";
        }

        if (isset($column['unsigned']) && $column['unsigned']) {
            $sql .= " UNSIGNED";
        }

        if (isset($column['nullable']) && $column['nullable']) {
            $sql .= " NULL";
        } else {
            $sql .= " NOT NULL";
        }

        if (isset($column['default'])) {
            $default = $column['default'];
            if (is_string($default)) $default = "'$default'";
            if (is_bool($default)) $default = $default ? '1' : '0';
            $sql .= " DEFAULT {$default}";
        }

        if (isset($column['auto_increment']) && $column['auto_increment']) {
            $sql .= " AUTO_INCREMENT";
        }

        if (isset($column['primary']) && $column['primary']) {
             // Basic primary key on the line or we can use the command
             // For simplicity, let's keep it here for auto_increment
             $sql .= " PRIMARY KEY";
        }

        return $sql;
    }

    protected function buildCommandSql(array $command): ?string
    {
        switch ($command['type']) {
            case 'unique':
                $name = $command['name'] ?? "uni_" . implode('_', $command['columns']);
                return "UNIQUE KEY `{$name}` (`" . implode('`, `', $command['columns']) . "`)";
            case 'index':
                $name = $command['name'] ?? "idx_" . implode('_', $command['columns']);
                return "KEY `{$name}` (`" . implode('`, `', $command['columns']) . "`)";
            case 'foreign':
                $name = "fk_{$this->table}_{$command['column']}";
                $sql = "CONSTRAINT `{$name}` FOREIGN KEY (`{$command['column']}`) REFERENCES `{$command['on']}` (`{$command['references']}`)";
                if (isset($command['onDelete'])) {
                    $sql .= " ON DELETE {$command['onDelete']}";
                }
                return $sql;
            default:
                return null;
        }
    }
}
