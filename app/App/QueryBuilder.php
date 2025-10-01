<?php

namespace TheFramework\App;

class QueryBuilder
{
    private $db;
    private $table;
    private $columns = "*";
    private $wherePairs = [];

    private $limit;
    private $offset;

    private $wheres = [];
    private $searches = [];
    private $joins = [];
    private $groupBy = [];
    private $orderBy = [];
    private $bindings = [];
    private $withRelations = [];

    /** @var Model */
    private $model;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
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
       WHERE
    --------------------------------*/
    public function where(string $column, string $operator, $value)
    {
        $param = ":where_" . count($this->bindings);

        $this->wheres[] = (strpos($column, '.') !== false)
            ? "$column $operator $param"
            : "`$column` $operator $param";

        $this->bindings[$param] = $value;

        if ($operator === '=' && strpos($column, '.') === false) {
            $this->wherePairs[$column] = $value;
        }

        return $this;
    }

    public function filter(string $column, $value)
    {
        return $this->where($column, '=', $value);
    }

    /* -------------------------------
       SEARCH
    --------------------------------*/
    public function search(array $columns, string $keyword)
    {
        if (!$keyword) return $this;

        $likeClauses = [];
        foreach ($columns as $i => $col) {
            $param = ":search_" . (count($this->bindings) + $i);
            $likeClauses[] = "`$col` LIKE $param";
            $this->bindings[$param] = "%$keyword%";
        }

        $this->searches[] = "(" . implode(" OR ", $likeClauses) . ")";
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
       JOIN
    --------------------------------*/
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'])) $type = 'INNER';
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
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $dir";
        return $this;
    }

    /* -------------------------------
       SQL Builder
    --------------------------------*/
    public function toSql(): string
    {
        $sql = "SELECT {$this->columns} FROM {$this->table}";

        // JOIN
        if (!empty($this->joins)) {
            $sql .= " " . implode(" ", $this->joins);
        }

        // WHERE
        $conditions = [];

        if (!empty($this->wheres)) {
            $whereClauses = [];
            foreach ($this->wheres as $w) {
                if (is_array($w) && isset($w['type']) && $w['type'] === 'raw') {
                    // whereRaw
                    $whereClauses[] = $w['condition'];
                } else {
                    // normal where/orWhere
                    $whereClauses[] = $w;
                }
            }
            if (!empty($whereClauses)) {
                $conditions[] = implode(" AND ", $whereClauses);
            }
        }

        // SEARCH
        if (!empty($this->searches)) {
            $conditions[] = implode(" AND ", $this->searches);
        }

        // gabungkan semua kondisi
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(", ", $this->groupBy);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(", ", $this->orderBy);
        }

        // LIMIT + OFFSET
        if (!is_null($this->limit)) {
            $sql .= " LIMIT {$this->limit}";
            if (!is_null($this->offset) && $this->offset > 0) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }


    /* -------------------------------
       EXECUTION
    --------------------------------*/
    public function get()
    {
        $sql = $this->toSql();
        $this->db->query($sql);

        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $results = $this->db->resultSet();

        // eager load
        if (!empty($this->withRelations) && $this->model) {
            foreach ($results as &$row) {
                foreach ($this->withRelations as $relation) {
                    if (method_exists($this->model, $relation)) {
                        $row[$relation] = $this->model->{$relation}($row);
                    }
                }
            }
        }

        return $results;
    }

    public function first()
    {
        $results = $this->limit(1)->get();
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
       PAGINATION
    --------------------------------*/
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = $this->toSql() . " LIMIT :limit OFFSET :offset";
        $this->db->query($sql);

        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $this->db->bind(':limit', $perPage);
        $this->db->bind(':offset', $offset);

        $data = $this->db->resultSet();

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

    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM ({$this->toSql()}) as sub";
        $this->db->query($sql);

        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $result = $this->db->single();
        return (int) ($result['total'] ?? 0);
    }

    /* -------------------------------
       EXTRA HELPERS
    --------------------------------*/
    public function whereRaw(string $condition, array $bindings = [])
    {
        foreach ($bindings as $i => $value) {
            $key = ':raw' . count($this->bindings);
            $condition = preg_replace('/\?/', $key, $condition, 1);
            $this->bindings[$key] = $value;
        }

        $this->wheres[] = ['type' => 'raw', 'condition' => $condition];
        return $this;
    }

    public function orWhere(string $column, string $operator, $value)
    {
        $param = ":where_" . count($this->bindings);
        $condition = (strpos($column, '.') !== false)
            ? "$column $operator $param"
            : "`$column` $operator $param";

        $this->bindings[$param] = $value;

        if (!empty($this->wheres)) {
            $last = array_pop($this->wheres);
            $this->wheres[] = "($last OR $condition)";
        } else {
            $this->wheres[] = $condition;
        }

        return $this;
    }

    public function limit(int $limit, int $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function with(array $relations)
    {
        $this->withRelations = $relations;
        return $this;
    }
}
