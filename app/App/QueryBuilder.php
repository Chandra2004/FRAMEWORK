<?php

namespace TheFramework\App;

class QueryBuilder
{
    private $db;
    private $table;
    private $columns = "*";
    private $wherePairs = [];

    private $wheres = [];
    private $searches = [];
    private $joins = [];
    private $groupBy = [];
    private $orderBy = [];
    private $bindings = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    public function select($columns = "*")
    {
        $this->columns = is_array($columns) ? implode(", ", $columns) : $columns;
        return $this;
    }

    /* -------------------------------
       BASIC WHERE / FILTER CONDITION
    --------------------------------*/
    // public function where(string $column, string $operator, $value) {
    //     $param = ":where_" . count($this->bindings);
    //     $this->wheres[] = "`$column` $operator $param";
    //     $this->bindings[$param] = $value;
    //     return $this;
    // }

    public function where(string $column, string $operator, $value)
    {
        $param = ":where_" . count($this->bindings);
        $this->wheres[] = "`$column` $operator $param";
        $this->bindings[$param] = $value;
        if ($operator === '=') {
            $this->wherePairs[$column] = $value;
        }
        return $this;
    }


    public function filter(string $column, $value)
    {
        return $this->where($column, '=', $value);
    }

    /* -------------------------------
       SEARCH & FULLTEXT SEARCH
    --------------------------------*/
    public function search(array $columns, string $keyword)
    {
        if (!$keyword) return $this;
        $param = ":search_" . count($this->bindings);
        $likeClauses = [];
        foreach ($columns as $col) {
            $likeClauses[] = "`$col` LIKE $param";
        }
        $this->searches[] = "(" . implode(" OR ", $likeClauses) . ")";
        $this->bindings[$param] = "%$keyword%";
        return $this;
    }

    public function fulltextSearch(array $columns, string $keyword)
    {
        if (!$keyword) return $this;
        $cols = implode(", ", $columns);
        $param = ":ft_" . count($this->bindings);
        $this->searches[] = "MATCH($cols) AGAINST ($param IN NATURAL LANGUAGE MODE)";
        $this->bindings[$param] = $keyword;
        return $this;
    }

    /* -------------------------------
       JOIN SUPPORT (MULTIPLE)
    --------------------------------*/
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) {
            $type = 'INNER';
        }
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    /* -------------------------------
       GROUP BY / ORDER BY
    --------------------------------*/
    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC')
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    /* -------------------------------
       BUILD SQL STRING
    --------------------------------*/
    public function toSql(): string
    {
        $sql = "SELECT {$this->columns} FROM {$this->table}";

        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        $conditions = [];
        if (!empty($this->wheres)) {
            $conditions[] = implode(" AND ", $this->wheres);
        }
        if (!empty($this->searches)) {
            $conditions[] = implode(" AND ", $this->searches);
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(", ", $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }

        return $sql;
    }

    /* -------------------------------
       EXECUTION HELPERS
    --------------------------------*/
    public function get()
    {
        $sql = $this->toSql();
        $this->db->query($sql);
        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        return $this->db->resultSet();
    }

    public function first()
    {
        $results = $this->get();
        return $results[0] ?? null;
    }


    public function insert(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update(array $data)
    {
        if (empty($this->wherePairs)) {
            throw new \InvalidArgumentException("Update tanpa WHERE dilarang");
        }
        return $this->db->update($this->table, $data, $this->wherePairs);
    }

    public function delete()
    {
        if (empty($this->wherePairs)) {
            throw new \InvalidArgumentException("Delete tanpa WHERE dilarang");
        }
        return $this->db->delete($this->table, $this->wherePairs);
    }


    /* -------------------------------
       PAGINATION SUPPORT
    --------------------------------*/
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;

        // Query data
        $sql = $this->toSql() . " LIMIT :limit OFFSET :offset";
        $this->db->query($sql);
        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $this->db->bind(':limit', $perPage);
        $this->db->bind(':offset', $offset);
        $data = $this->db->resultSet();

        // Query hitung total data
        $countSql = "SELECT COUNT(*) as total FROM ({$this->toSql()}) as sub";
        $this->db->query($countSql);
        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $total = $this->db->single()['total'];

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }
}
