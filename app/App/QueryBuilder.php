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
    private $cacheTtl = 0; // Cache duration in seconds

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

    // -------------------------
    // WHERE CLAUSES
    // -------------------------
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

    public function whereIn(string $column, array $values)
    {
        if (empty($values)) {
            $this->wheres[] = "0=1";
            return $this;
        }

        $placeholders = [];
        foreach ($values as $value) {
            $param = ":in_" . count($this->bindings);
            $placeholders[] = $param;
            $this->bindings[$param] = $value;
        }

        $columnName = (strpos($column, '.') !== false) ? $column : "`$column`";
        $this->wheres[] = "$columnName IN (" . implode(", ", $placeholders) . ")";
        return $this;
    }

    public function whereNotIn(string $column, array $values)
    {
        if (empty($values)) {
            return $this;
        }

        $placeholders = [];
        foreach ($values as $value) {
            $param = ":notin_" . count($this->bindings);
            $placeholders[] = $param;
            $this->bindings[$param] = $value;
        }

        $columnName = (strpos($column, '.') !== false) ? $column : "`$column`";
        $this->wheres[] = "$columnName NOT IN (" . implode(", ", $placeholders) . ")";
        return $this;
    }


    public function filter(string $column, $value)
    {
        return $this->where($column, '=', $value);
    }

    // -------------------------
    // SEARCH
    // -------------------------
    public function search(array $columns, string $keyword)
    {
        if (!$keyword)
            return $this;

        $likeClauses = [];
        foreach ($columns as $i => $col) {
            $param = ":search_" . (count($this->bindings) + $i);
            $likeClauses[] = "`$col` LIKE $param";
            $this->bindings[$param] = "%$keyword%";
        }

        $this->searches[] = "(" . implode(" OR ", $likeClauses) . ")";
        return $this;
    }

    // -------------------------
    // JOINS
    // -------------------------
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        $type = strtoupper($type);
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT']))
            $type = 'INNER';
        $this->joins[] = "$type JOIN $table ON $first $operator $second";
        return $this;
    }

    // -------------------------
    // GROUP / ORDER BY
    // -------------------------
    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    private $lock; // Untuk Pessimistic Locking (FOR UPDATE)

    // ... (kode existing) ...

    public function orderBy(string $column, string $direction = 'ASC')
    {
        // Security: Validasi nama kolom (hanya alfanumerik, underscore, titik)
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $column)) {
            // Atau throw exception jika ingin strict
            throw new \InvalidArgumentException("Invalid column name for orderBy: $column");
        }

        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy[] = "$column $dir";
        return $this;
    }

    public function orderByRaw(string $expression)
    {
        $this->orderBy[] = $expression;
        return $this;
    }

    /**
     * Lock row untuk update (Pessimistic Locking).
     * Sangat PENTING untuk sistem tiket/stok (mencegah race condition).
     * Harus dipanggil di dalam transaction.
     */
    public function lockForUpdate()
    {
        $this->lock = "FOR UPDATE";
        return $this;
    }

    /**
     * Lock row untuk share (Shared Lock).
     */
    public function sharedLock()
    {
        $this->lock = "LOCK IN SHARE MODE";
        return $this;
    }

    /* --------------------------------
    SQL Builder
    ---------------------------------*/
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

        if (!is_null($this->limit)) {
            $sql .= " LIMIT {$this->limit}";
            if (!is_null($this->offset) && $this->offset > 0) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        // Append Lock Clause di akhir query
        if ($this->lock) {
            $sql .= " " . $this->lock;
        }

        return $sql;
    }

    // -------------------------
    // EXECUTION METHODS
    // -------------------------
    public function get()
    {
        // Check cache if TTL > 0
        if ($this->cacheTtl > 0 && class_exists('\\TheFramework\\App\\CacheManager')) {
            $cacheKey = $this->getCacheKey();

            return \TheFramework\App\CacheManager::remember($cacheKey, $this->cacheTtl, function () {
                return $this->executeQuery();
            });
        }

        return $this->executeQuery();
    }

    private function executeQuery()
    {
        $sql = $this->toSql();
        $this->db->query($sql);

        foreach ($this->bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $results = $this->db->resultSet();

        if ($this->model) {
            return $this->model->loadRelations($results, $this->withRelations);
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

        // Otomatis tambahkan updated_at jika belum ada di data
        // User bisa override dengan menyediakan updated_at secara eksplisit
        if (!isset($data['updated_at'])) {
            // Gunakan Helper jika tersedia, atau gunakan date() sebagai fallback
            if (class_exists('\\TheFramework\\Helpers\\Helper')) {
                $data['updated_at'] = \TheFramework\Helpers\Helper::updateAt();
            } else {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
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

    // -------------------------
    // PAGINATION
    // -------------------------
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

        // eager load juga
        if ($this->model) {
            $data = $this->model->loadRelations($data, $this->withRelations);
        }

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

    // -------------------------
    // HELPERS
    // -------------------------
    public function whereRaw(string $condition, array $bindings = [])
    {
        foreach ($bindings as $i => $value) {
            $key = ':raw' . count($this->bindings);
            $condition = preg_replace('/\?/', $key, $condition, 1);
            $this->bindings[$key] = $value;
        }

        $this->wheres[] = $condition;
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

    // -------------------------
    // EAGER LOADING
    // -------------------------
    public function with(array $relations)
    {
        $this->withRelations = $relations;

        if ($this->model) {
            $this->model->with($relations);
        }

        return $this;
    }

    public function all()
    {
        return $this->get();
    }

    public function map(callable $callback): array
    {
        $results = $this->get();
        return array_map($callback, $results);
    }

    // -------------------------
    // PLUCK
    // -------------------------
    public function pluck(string $column): array
    {
        $results = $this->get();

        if (empty($results)) {
            return [];
        }

        $values = [];
        foreach ($results as $row) {
            if (is_array($row) && array_key_exists($column, $row)) {
                $values[] = $row[$column];
            } elseif (is_object($row) && isset($row->$column)) {
                $values[] = $row->$column;
            }
        }

        return $values;
    }

    // -------------------------
    // CACHING
    // -------------------------
    public function remember(int $seconds)
    {
        $this->cacheTtl = $seconds;
        return $this;
    }

    private function getCacheKey(): string
    {
        $sql = $this->toSql();
        $bindings = serialize($this->bindings);
        $with = serialize($this->withRelations);
        return 'qry_' . md5($sql . $bindings . $with);
    }
}
