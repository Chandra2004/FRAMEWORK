<?php

namespace TheFramework\App\Database;

use InvalidArgumentException;
use Closure;
use TheFramework\App\Exceptions\ModelNotFoundException;

class QueryBuilder
{
    private $db;
    private $table;
    private $columns = "*";
    private bool $distinct = false;
    private array $indexHints = [];

    private $limit;
    private $offset;

    private $cacheTtl = null;
    private $cacheKey = null;

    private $wheres = [];
    private $havings = [];
    private $joins = [];
    private $groupBy = [];
    private $orders = [];

    private $unions = [];

    private $withRelations = [];
    private $withCounts = [];
    private $withAggregates = [];

    /** @var Model */
    private $model;
    private $lock;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function setModel($model)
    {
        $this->model = $model;
        $this->table = $model->getTable();
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function table(string $table)
    {
        $this->table = $table;
        return $this;
    }

    private function wrapColumn($column)
    {
        if ($column instanceof Raw) {
            return $column->getValue();
        }
        if (strpos($column, '.') !== false || strpos($column, '(') !== false) {
            return $column;
        }
        if (strpos($column, '.') === false) {
            return "`{$this->table}`.`{$column}`";
        }
        return "`{$column}`";
    }

    public function select($columns = ["*"])
    {
        $rawColumns = is_array($columns) ? $columns : func_get_args();
        $this->columns = implode(", ", array_map(function ($item) {
            return (string) $item;
        }, $rawColumns));
        return $this;
    }

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function from(string $table, ?string $alias = null)
    {
        $this->table = $table . ($alias ? " AS $alias" : '');
        return $this;
    }

    public function fromRaw(string $expression)
    {
        $this->table = new Raw($expression);
        return $this;
    }

    public function fromSub($query, string $alias)
    {
        if ($query instanceof QueryBuilder) {
            list($sql, $bindings) = $query->toSql();
            $this->table = new Raw("({$sql}) as {$alias}");
            // merge bindings later when executing
            $this->unions = array_merge($this->unions, $bindings);
        }
        return $this;
    }

    public function useIndex(string $index)
    {
        $this->indexHints[] = "USE INDEX (`{$index}`)";
        return $this;
    }

    public function forceIndex(string $index)
    {
        $this->indexHints[] = "FORCE INDEX (`{$index}`)";
        return $this;
    }

    public function ignoreIndex(string $index)
    {
        $this->indexHints[] = "IGNORE INDEX (`{$index}`)";
        return $this;
    }

    public function orderByRaw(string $expression)
    {
        $this->orders[] = ['column' => new Raw($expression), 'direction' => ''];
        return $this;
    }

    public function orderBySub($query, string $direction = 'ASC')
    {
        if ($query instanceof QueryBuilder) {
            list($sql, $bindings) = $query->toSql();
            $this->orders[] = ['column' => new Raw("({$sql})"), 'direction' => $direction];
            $this->unions = array_merge($this->unions, $bindings);
        }
        return $this;
    }

    public function inRandomOrder()
    {
        return $this->orderByRaw('RAND()');
    }

    public function reorder(string $column, string $direction = 'ASC')
    {
        $this->orders = [];
        return $this->orderBy($column, $direction);
    }

    public function addSelect($columns)
    {
        $rawColumns = is_array($columns) ? $columns : func_get_args();
        $formatted = array_map(function ($item) {
            return (string) $item;
        }, $rawColumns);

        if ($this->columns === '*') {
            $this->columns = implode(", ", $formatted);
        } else {
            $this->columns .= ", " . implode(", ", $formatted);
        }

        return $this;
    }

    public function selectRaw(string $expression, array $bindings = [])
    {
        $this->select(new Raw($expression));
        return $this;
    }

    /**
     * Get a plain SQL representation of the query with values bound.
     */
    public function toRawSql(): string
    {
        [$sql, $bindings] = $this->toSql();

        // Sort bindings by key length descending to avoid partial replacement issues (e.g. :id vs :id_2)
        uksort($bindings, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        foreach ($bindings as $key => $value) {
            $quoted = is_string($value) ? "'$value'" : $value;
            if (is_null($value))
                $quoted = 'NULL';
            $sql = str_replace($key, $quoted, $sql);
        }
        return $sql;
    }

    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND')
    {
        $this->wheres[] = [
            'type' => 'raw',
            'sql' => $sql,
            'bindings' => $bindings,
            'boolean' => $boolean
        ];
        return $this;
    }

    public function orWhereRaw(string $sql, array $bindings = [])
    {
        return $this->whereRaw($sql, $bindings, 'OR');
    }

    public function whereNested(Closure $callback, $boolean = 'AND')
    {
        $query = $this->model->newQueryWithoutScopes();
        $callback($query);
        $this->wheres[] = [
            'type' => 'nested',
            'query' => $query,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        if ($column instanceof Closure) {
            return $this->whereNested($column, 'OR');
        }
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, $boolean = 'AND', $not = false)
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function whereNotIn(string $column, array $values, $boolean = 'AND')
    {
        return $this->whereIn($column, $values, $boolean, true);
    }

    public function whereKey($id)
    {
        $primaryKey = $this->model ? $this->model->getKeyName() : 'id';
        if (is_array($id)) {
            return $this->whereIn($primaryKey, $id);
        }
        return $this->where($primaryKey, '=', $id);
    }

    public function whereKeyNot($id)
    {
        $primaryKey = $this->model ? $this->model->getKeyName() : 'id';
        if (is_array($id)) {
            return $this->whereNotIn($primaryKey, $id);
        }
        return $this->where($primaryKey, '!=', $id);
    }

    public function whereColumn(string $first, string $operator, string $second, string $boolean = 'AND')
    {
        $this->wheres[] = [
            'type' => 'column',
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => $boolean
        ];
        return $this;
    }

    public function whereNot($column, $operator = null, $value = null, $boolean = 'AND')
    {
        return $this->where($column, $operator, $value, $boolean . ' NOT');
    }

    // syntactic sugar: whereAny / whereAll / whereNone
    public function whereAny(array $columns, $operator, $value)
    {
        $this->wheres[] = ['type' => 'nested', 'query' => null, 'boolean' => 'AND'];
        $query = $this->model->newQueryWithoutScopes();
        foreach ($columns as $col) {
            $query->orWhere($col, $operator, $value);
        }
        $this->wheres[count($this->wheres) - 1]['query'] = $query;
        return $this;
    }
    public function whereAll(array $columns, $operator, $value)
    {
        foreach ($columns as $col) {
            $this->where($col, $operator, $value);
        }
        return $this;
    }
    public function whereNone(array $columns, $operator, $value)
    {
        $query = $this->model->newQueryWithoutScopes();
        foreach ($columns as $col) {
            $query->orWhereNot($col, $operator, $value);
        }
        $this->wheres[] = ['type' => 'nested', 'query' => $query, 'boolean' => 'AND'];
        return $this;
    }

    public function whereBetween(string $column, array $values, $boolean = 'AND', $not = false)
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function whereNotBetween(string $column, array $values, $boolean = 'AND')
    {
        return $this->whereBetween($column, $values, $boolean, true);
    }

    public function whereNull(string $column, $boolean = 'AND', $not = false)
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function whereNotNull(string $column, $boolean = 'AND')
    {
        return $this->whereNull($column, $boolean, true);
    }

    public function whereDate($column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addDateBasedWhere('Date', $column, $operator, $value, $boolean);
    }

    public function whereMonth($column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addDateBasedWhere('Month', $column, $operator, $value, $boolean);
    }

    public function whereDay($column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addDateBasedWhere('Day', $column, $operator, $value, $boolean);
    }

    public function whereYear($column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addDateBasedWhere('Year', $column, $operator, $value, $boolean);
    }

    public function whereTime($column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->addDateBasedWhere('Time', $column, $operator, $value, $boolean);
    }

    protected function addDateBasedWhere($type, $column, $operator, $value, $boolean)
    {
        $this->wheres[] = [
            'type' => 'date',
            'date_type' => $type,
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function whereJsonContains(string $column, $value, $boolean = 'AND', $not = false)
    {
        $this->wheres[] = [
            'type' => 'json_contains',
            'column' => $column,
            'value' => $value,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function whereJsonLength(string $column, $operator, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = [
            'type' => 'json_length',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function whereHas(string $relationName, ?Closure $callback = null, $boolean = 'AND')
    {
        return $this->addSubqueryWhere('exists', $relationName, $boolean, $callback);
    }

    public function orWhereHas(string $relationName, ?Closure $callback = null)
    {
        return $this->whereHas($relationName, $callback, 'OR');
    }

    public function doesntHave(string $relation, $boolean = 'AND', ?Closure $callback = null)
    {
        return $this->addSubqueryWhere('not_exists', $relation, $boolean, $callback);
    }

    public function orDoesntHave(string $relation, ?Closure $callback = null)
    {
        return $this->doesntHave($relation, 'OR', $callback);
    }

    public function whereRelation(string $relationName, string $column, $operator, $value = null)
    {
        return $this->whereHas($relationName, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    // helper tanggal Laravel 11
    public function wherePast(string $column)
    {
        return $this->where($column, '<', date('Y-m-d H:i:s'));
    }
    public function whereFuture(string $column)
    {
        return $this->where($column, '>', date('Y-m-d H:i:s'));
    }
    public function whereNowOrPast(string $column)
    {
        return $this->where($column, '<=', date('Y-m-d H:i:s'));
    }
    public function whereNowOrFuture(string $column)
    {
        return $this->where($column, '>=', date('Y-m-d H:i:s'));
    }
    public function whereToday(string $column)
    {
        return $this->whereDate($column, date('Y-m-d'));
    }
    public function whereBeforeToday(string $column)
    {
        return $this->whereDate($column, '<', date('Y-m-d'));
    }
    public function whereAfterToday(string $column)
    {
        return $this->whereDate($column, '>', date('Y-m-d'));
    }
    public function whereTodayOrBefore(string $column)
    {
        return $this->whereDate($column, '<=', date('Y-m-d'));
    }
    public function whereTodayOrAfter(string $column)
    {
        return $this->whereDate($column, '>=', date('Y-m-d'));
    }

    public function orWhereRelation(string $relationName, string $column, $operator, $value = null)
    {
        return $this->orWhereHas($relationName, function ($query) use ($column, $operator, $value) {
            $query->where($column, $operator, $value);
        });
    }

    protected function addSubqueryWhere($type, $relationName, $boolean, ?Closure $callback = null)
    {
        $relation = $this->model->$relationName();
        $subQuery = $relation->getRelated()->newQueryWithoutScopes();
        $subQuery->whereColumn($relation->getQualifiedForeignKeyName(), '=', $this->model->getQualifiedKeyName());

        if ($callback)
            $callback($subQuery);

        $this->wheres[] = [
            'type' => $type,
            'subquery' => $subQuery,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER')
    {
        $this->joins[] = "{$type} JOIN `{$table}` ON {$first} {$operator} {$second}";
        return $this;
    }

    public function innerJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'INNER');
    }

    public function leftJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function leftOuterJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT OUTER');
    }

    public function rightJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    public function rightOuterJoin(string $table, string $first, string $operator, string $second)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT OUTER');
    }

    public function fullOuterJoin(string $table, string $first, string $operator, string $second)
    {
        // MySQL doesn't natively support FULL OUTER JOIN, but for DX we'll pass it if requested
        // Or handle it via UNION in better implementation. For now just pass SQL.
        return $this->join($table, $first, $operator, $second, 'FULL OUTER');
    }

    public function crossJoin(string $table)
    {
        $this->joins[] = "CROSS JOIN `{$table}`";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC')
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = ['column' => $column, 'direction' => $dir];
        return $this;
    }

    // HAVING helpers
    public function having($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->havings[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function orHaving($column, $operator = null, $value = null)
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    public function havingRaw(string $expression, array $bindings = [], $boolean = 'AND')
    {
        $this->havings[] = [
            'type' => 'raw',
            'expression' => $expression,
            'bindings' => $bindings,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function orHavingRaw(string $expression, array $bindings = [])
    {
        return $this->havingRaw($expression, $bindings, 'OR');
    }

    public function havingBetween(string $column, array $values, $boolean = 'AND', $not = false)
    {
        $this->havings[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function orHavingBetween(string $column, array $values)
    {
        return $this->havingBetween($column, $values, 'OR');
    }

    public function havingNull(string $column, $boolean = 'AND', $not = false)
    {
        $this->havings[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
            'not' => $not,
        ];
        return $this;
    }

    public function havingNotNull(string $column, $boolean = 'AND')
    {
        return $this->havingNull($column, $boolean, true);
    }

    public function orHavingNull(string $column)
    {
        return $this->havingNull($column, 'OR');
    }

    public function orHavingNotNull(string $column)
    {
        return $this->havingNull($column, 'OR', true);
    }

    public function latest(string $column = 'created_at')
    {
        return $this->orderBy($column, 'DESC');
    }

    public function oldest(string $column = 'created_at')
    {
        return $this->orderBy($column, 'ASC');
    }

    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    public function when($value, callable $callback, ?callable $default = null)
    {
        if ($value)
            return $callback($this, $value) ?? $this;
        elseif ($default)
            return $default($this, $value) ?? $this;
        return $this;
    }

    private function compileWheres(): array
    {
        if (empty($this->wheres))
            return ['', []];
        $sqlParts = [];
        $bindings = [];
        $counter = 0;
        foreach ($this->wheres as $i => $where) {
            $boolean = ($i === 0) ? '' : $where['boolean'];
            $part = '';
            switch ($where['type']) {
                case 'basic':
                    $paramName = ":where_{$counter}";
                    $part = "{$this->wrapColumn($where['column'])} {$where['operator']} {$paramName}";
                    $bindings[$paramName] = $where['value'];
                    $counter++;
                    break;
                case 'in':
                    if (empty($where['values'])) {
                        $part = $where['not'] ? '1=1' : '0=1';
                    } else {
                        $inPlaceholders = [];
                        foreach ($where['values'] as $value) {
                            $inParam = ":in_{$counter}";
                            $inPlaceholders[] = $inParam;
                            $bindings[$inParam] = $value;
                            $counter++;
                        }
                        $operator = $where['not'] ? 'NOT IN' : 'IN';
                        $part = $this->wrapColumn($where['column']) . " {$operator} (" . implode(", ", $inPlaceholders) . ")";
                    }
                    break;
                case 'column':
                    $part = "{$this->wrapColumn($where['first'])} {$where['operator']} {$this->wrapColumn($where['second'])}";
                    break;
                case 'nested':
                    [$nestedSql, $nestedBindings] = $where['query']->compileWheres();
                    $part = "(" . ltrim(ltrim($nestedSql, 'WHERE ')) . ")";
                    $bindings = array_merge($bindings, $nestedBindings);
                    break;
                case 'exists':
                case 'not_exists':
                    [$subSql, $subBindings] = $where['subquery']->toSql();
                    $operator = $where['type'] === 'exists' ? 'EXISTS' : 'NOT EXISTS';
                    $part = "{$operator} ({$subSql})";
                    $bindings = array_merge($bindings, $subBindings);
                    break;
                case 'between':
                    $param1 = ":btw_{$counter}";
                    $counter++;
                    $param2 = ":btw_{$counter}";
                    $counter++;
                    $bindings[$param1] = $where['values'][0];
                    $bindings[$param2] = $where['values'][1];
                    $operator = $where['not'] ? 'NOT BETWEEN' : 'BETWEEN';
                    $part = "{$this->wrapColumn($where['column'])} {$operator} {$param1} AND {$param2}";
                    break;
                case 'null':
                    $operator = $where['not'] ? 'IS NOT NULL' : 'IS NULL';
                    $part = "{$this->wrapColumn($where['column'])} {$operator}";
                    break;
                case 'date':
                    $paramName = ":date_{$counter}";
                    $part = "{$where['date_type']}({$this->wrapColumn($where['column'])}) {$where['operator']} {$paramName}";
                    $bindings[$paramName] = $where['value'];
                    $counter++;
                    break;
                case 'json_contains':
                    $paramName = ":json_{$counter}";
                    $operator = $where['not'] ? 'NOT ' : '';
                    $part = "{$operator}JSON_CONTAINS({$this->wrapColumn($where['column'])}, {$paramName})";
                    $bindings[$paramName] = json_encode($where['value']);
                    $counter++;
                    break;
                case 'json_length':
                    $paramName = ":json_len_{$counter}";
                    $part = "JSON_LENGTH({$this->wrapColumn($where['column'])}) {$where['operator']} {$paramName}";
                    $bindings[$paramName] = $where['value'];
                    $counter++;
                    break;
                case 'raw':
                    $part = $where['sql'];
                    $bindings = array_merge($bindings, $where['bindings']);
                    break;
            }
            $sqlParts[] = "{$boolean} {$part}";
        }
        $sql = implode(' ', $sqlParts);
        if (!empty($sql))
            $sql = 'WHERE ' . ltrim(ltrim($sql), 'AND ');
        return [$sql, $bindings];
    }

    private function compileHavings(): array
    {
        if (empty($this->havings))
            return ['', []];
        $sqlParts = [];
        $bindings = [];
        $counter = 0;
        foreach ($this->havings as $i => $having) {
            $boolean = ($i === 0) ? '' : $having['boolean'];
            $part = '';
            switch ($having['type']) {
                case 'basic':
                    $param = ":having_{$counter}";
                    $part = "{$having['column']} {$having['operator']} {$param}";
                    $bindings[$param] = $having['value'];
                    $counter++;
                    break;
                case 'raw':
                    $part = $having['expression'];
                    if (!empty($having['bindings'])) {
                        foreach ($having['bindings'] as $param => $val) {
                            $bindings[$param] = $val;
                        }
                    }
                    break;
                case 'between':
                    $p1 = ":hav_{$counter}";
                    $counter++;
                    $p2 = ":hav_{$counter}";
                    $counter++;
                    $bindings[$p1] = $having['values'][0];
                    $bindings[$p2] = $having['values'][1];
                    $op = $having['not'] ? 'NOT BETWEEN' : 'BETWEEN';
                    $part = "{$having['column']} {$op} {$p1} AND {$p2}";
                    break;
                case 'null':
                    $op = $having['not'] ? 'IS NOT NULL' : 'IS NULL';
                    $part = "{$having['column']} {$op}";
                    break;
            }
            $sqlParts[] = "{$boolean} {$part}";
        }
        $sql = implode(' ', $sqlParts);
        if (!empty($sql))
            $sql = 'HAVING ' . ltrim(ltrim($sql), 'AND ');
        return [$sql, $bindings];
    }

    public function toSql(): array
    {
        // build select portion, handle DISTINCT
        $sql = "SELECT " . ($this->distinct ? 'DISTINCT ' : '') . "{$this->columns} FROM `{$this->table}`";

        // index hints
        if (!empty($this->indexHints)) {
            $sql .= ' ' . implode(' ', $this->indexHints);
        }

        if (!empty($this->joins))
            $sql .= " " . implode(" ", $this->joins);
        [$whereSql, $whereBindings] = $this->compileWheres();
        $sql .= " " . $whereSql;
        $finalBindings = $whereBindings;
        if (!empty($this->groupBy))
            $sql .= " GROUP BY " . implode(", ", $this->groupBy);

        // having clauses
        [$havingSql, $havingBindings] = $this->compileHavings();
        $sql .= " " . $havingSql;
        $finalBindings = array_merge($finalBindings, $havingBindings);

        if (!empty($this->orders)) {
            $orderClauses = [];
            foreach ($this->orders as $order) {
                if ($order['column'] instanceof Raw) {
                    $orderClauses[] = (string) $order['column'] . ' ' . $order['direction'];
                } else {
                    $orderClauses[] = "{$this->wrapColumn($order['column'])} {$order['direction']}";
                }
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT :main_limit";
            $finalBindings[':main_limit'] = (int) $this->limit;
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET :main_offset";
            $finalBindings[':main_offset'] = (int) $this->offset;
        }
        if ($this->lock)
            $sql .= " " . $this->lock;

        // union clauses
        if (!empty($this->unions)) {
            foreach ($this->unions as $union) {
                $sql .= " UNION " . ($union['all'] ? 'ALL ' : '') . $union['query'];
                if (!empty($union['bindings'])) {
                    $finalBindings = array_merge($finalBindings, $union['bindings']);
                }
            }
        }

        return [$sql, $finalBindings];
    }

    public function get()
    {
        return $this->executeQuery();
    }

    public function union(QueryBuilder $query, $all = false)
    {
        list($sql, $bindings) = $query->toSql();
        $this->unions[] = ['query' => $sql, 'bindings' => $bindings, 'all' => $all];
        return $this;
    }

    public function unionAll(QueryBuilder $query)
    {
        return $this->union($query, true);
    }
    public function all()
    {
        return $this->get();
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $originalColumns = $this->columns;
        $this->columns = $key ? "{$this->wrapColumn($column)}, {$this->wrapColumn($key)}" : $this->wrapColumn($column);
        $results = $this->get();
        $this->columns = $originalColumns;
        $plain = [];
        foreach ($results as $row) {
            $rowArray = (array) $row;
            $val = $rowArray[$column] ?? null;
            if ($key)
                $plain[$rowArray[$key]] = $val;
            else
                $plain[] = $val;
        }
        return $plain;
    }

    public function value(string $column)
    {
        $originalColumns = $this->columns;
        $this->columns = $this->wrapColumn($column);
        $result = $this->first();
        $this->columns = $originalColumns;
        if ($result)
            return is_object($result) ? $result->getAttribute($column) : ($result[$column] ?? null);
        return null;
    }

    public function soleValue(string $column)
    {
        $originalColumns = $this->columns;
        $this->columns = $this->wrapColumn($column);
        $result = $this->sole();
        $this->columns = $originalColumns;
        if ($result)
            return is_object($result) ? $result->getAttribute($column) : ($result[$column] ?? null);
        return null;
    }

    public function rawValue(string $expression)
    {
        $originalColumns = $this->columns;
        $this->columns = $expression;
        $result = $this->first();
        $this->columns = $originalColumns;

        if ($result) {
            $rowArray = (array) $result;
            return reset($rowArray);
        }
        return null;
    }

    public function valueOrFail(string $column)
    {
        $value = $this->value($column);
        if (is_null($value))
            throw new ModelNotFoundException("No value found for column [$column]");
        return $value;
    }

    public function implode(string $column, string $glue = ''): string
    {
        return implode($glue, $this->pluck($column));
    }

    private function executeQuery()
    {
        [$sql, $bindings] = $this->toSql();

        if ($this->cacheTtl !== null) {
            $key = $this->cacheKey ?? 'query_' . md5($sql . serialize($bindings));
            return \TheFramework\App\Cache\CacheManager::remember($key, $this->cacheTtl, function () use ($sql, $bindings) {
                return $this->rawExecute($sql, $bindings);
            });
        }

        return $this->rawExecute($sql, $bindings);
    }

    protected function rawExecute(string $sql, array $bindings)
    {
        $this->db->query($sql);
        foreach ($bindings as $param => $value)
            $this->db->bind($param, $value);
        $this->db->execute();
        $results = $this->db->resultSet();

        if ($this->model) {
            $models = $this->getModels($results);
            if (!empty($this->withRelations))
                $models = $this->model->loadRelations($models, $this->withRelations);
            if (!empty($this->withCounts))
                $models = $this->model->loadCounts($models, $this->withCounts);
            if (!empty($this->withAggregates))
                $models = $this->model->loadAggregates($models, $this->withAggregates);
            return $models;
        }
        return $results;
    }

    public function getModels(array $results): array
    {
        return $this->model->hydrate($results);
    }

    public function first($columns = ['*'])
    {
        if ($columns !== ['*'])
            $this->select($columns);
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function firstOr($columns = ['*'], ?Closure $callback = null)
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        if (!is_null($model = $this->first($columns))) {
            return $model;
        }

        return $callback();
    }

    public function sole($columns = ['*'])
    {
        if ($columns !== ['*'])
            $this->select($columns);
        $results = $this->take(2)->get();

        if (count($results) === 0) {
            throw new ModelNotFoundException("No records found.");
        }

        if (count($results) > 1) {
            throw new \Exception("Multiple records found for sole query.");
        }

        return $results[0];
    }

    public function find($id, $columns = ['*'])
    {
        if ($columns !== ['*'])
            $this->select($columns);
        if (is_array($id))
            return $this->whereIn($this->model ? $this->model->getKeyName() : 'id', $id)->get();
        $primaryKey = $this->model ? $this->model->getKeyName() : 'id';
        return $this->where($primaryKey, '=', $id)->first();
    }

    public function findOr($id, $columns = ['*'], ?Closure $callback = null)
    {
        if ($columns instanceof Closure) {
            $callback = $columns;
            $columns = ['*'];
        }

        if (!is_null($model = $this->find($id, $columns))) {
            return $model;
        }

        return $callback();
    }

    public function findSole($id, $columns = ['*'])
    {
        return $this->where($this->model ? $this->model->getKeyName() : 'id', '=', $id)->sole($columns);
    }

    public function findMany(array $ids)
    {
        return $this->find($ids);
    }

    public function firstOrFail($columns = ['*'])
    {
        $result = $this->first($columns);
        if (is_null($result))
            throw new ModelNotFoundException("No results found.");
        return $result;
    }

    public function firstWhere($column, $operator = null, $value = null)
    {
        return $this->where(...func_get_args())->first();
    }

    public function findOrFail($id, $columns = ['*'])
    {
        $result = $this->find($id, $columns);
        if (is_null($result) || (is_array($id) && count($result) < count($id))) {
            throw new ModelNotFoundException("No query results for model [" . get_class($this->model) . "] " . (is_array($id) ? implode(',', $id) : $id));
        }
        return $result;
    }

    public function findOrNew($id)
    {
        if (!is_null($model = $this->find($id)))
            return $model;
        return $this->make();
    }

    public function make(array $attributes = [])
    {
        return $this->model->newInstance($attributes);
    }

    public function fromQuery(string $query, array $bindings = [])
    {
        $this->db->query($query);
        foreach ($bindings as $param => $value)
            $this->db->bind($param, $value);
        $this->db->execute();
        $results = $this->db->resultSet();
        return $this->model ? $this->model->hydrate($results) : $results;
    }

    public function qualifyColumn(string $column): string
    {
        return $this->wrapColumn($column);
    }

    public function insertGetId(array $data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->lastInsertId();
    }

    /**
     * Create a new model instance and save it.
     */
    public function create(array $data)
    {
        $model = $this->model->newInstance($data);
        $model->save();
        return $model;
    }

    /**
     * Destroy records by primary key (single or array).
     */
    public function destroy($ids): int
    {
        if (is_array($ids)) {
            return $this->whereIn($this->model->getKeyName(), $ids)->delete();
        }
        return $this->where($this->model->getKeyName(), '=', $ids)->delete();
    }

    public function insert(array $data): int
    {
        if ($this->db->insert($this->table, $data)) {
            return $this->db->rowCount();
        }
        return 0;
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        $instance = $this->where($attributes)->first();
        if (!is_null($instance)) {
            return $instance;
        }
        return $this->model->create(array_merge($attributes, $values));
    }

    public function updateOrCreate(array $attributes, array $values)
    {
        $instance = $this->where($attributes)->first();
        if (!is_null($instance)) {
            $instance->fill($values)->save();
            return $instance;
        }
        return $this->model->create(array_merge($attributes, $values));
    }

    public function upsert(array $values, array $uniqueBy, array $update)
    {
        if (empty($values))
            return 0;

        $columns = array_keys($values[0]);
        $columnSql = '`' . implode('`, `', $columns) . '`';

        $bindings = [];
        $valuesSqlParts = [];
        $i = 0;

        foreach ($values as $row) {
            $rowPlaceholders = [];
            foreach ($row as $column => $value) {
                $paramName = ":upsert_{$i}";
                $rowPlaceholders[] = $paramName;
                $bindings[$paramName] = $value;
                $i++;
            }
            $valuesSqlParts[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $valuesSql = implode(', ', $valuesSqlParts);

        $updateSqlParts = [];
        foreach ($update as $col) {
            $updateSqlParts[] = "`{$col}` = VALUES(`{$col}`)";
        }
        $updateSql = implode(', ', $updateSqlParts);

        $sql = "INSERT INTO `{$this->table}` ({$columnSql}) VALUES {$valuesSql} ON DUPLICATE KEY UPDATE {$updateSql}";

        $this->db->query($sql);
        foreach ($bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function update(array $data): int
    {
        if (empty($this->wheres)) {
            throw new \Exception('Update without a WHERE clause is not allowed for safety.');
        }

        $setParts = [];
        $updateBindings = [];
        foreach ($data as $column => $value) {
            $paramName = ":update_" . $column;
            $setParts[] = "{$this->wrapColumn($column)} = {$paramName}";
            $updateBindings[$paramName] = $value;
        }
        $setSql = implode(', ', $setParts);

        [$whereSql, $whereBindings] = $this->compileWheres();
        $finalBindings = array_merge($updateBindings, $whereBindings);

        $sql = "UPDATE `{$this->table}` SET {$setSql} {$whereSql}";

        $this->db->query($sql);
        foreach ($finalBindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function delete(): int
    {
        if (empty($this->wheres)) {
            throw new \Exception('Delete without a WHERE clause is not allowed for safety.');
        }

        [$whereSql, $whereBindings] = $this->compileWheres();
        $sql = "DELETE FROM `{$this->table}` {$whereSql}";

        $this->db->query($sql);
        foreach ($whereBindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $this->db->execute();
        return $this->db->rowCount();
    }

    public function count(): int
    {
        $countBuilder = clone $this;
        $countBuilder->columns = 'COUNT(*) as total';

        [$sql, $bindings] = $countBuilder->toSql();

        // if unions present wrap into subquery
        if (!empty($this->unions)) {
            $sql = "SELECT COUNT(*) as total FROM ({$sql}) as temp";
            // bindings already contain where/having numbers, union clauses may have their own.
            // We'll need to merge them separately but for now assume toSql returned full sql with unions.
        } else {
            $sql = preg_replace('/ ORDER BY .*$/i', '', $sql);
            $sql = preg_replace('/ LIMIT .*$/i', '', $sql);
            $sql = preg_replace('/ OFFSET .*$/i', '', $sql);
        }

        $this->db->query($sql);
        foreach ($bindings as $param => $value) {
            $this->db->bind($param, $value);
        }

        $this->db->execute();
        $result = $this->db->single();
        return (int) ($result['total'] ?? 0);
    }

    public function chunk(int $count, callable $callback)
    {
        $page = 1;
        do {
            $results = $this->limit($count)->offset(($page - 1) * $count)->get();
            $countResults = count($results);

            if ($countResults == 0)
                break;

            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);
            $page++;
        } while ($countResults == $count);
        return true;
    }

    public function chunkById(int $count, callable $callback, $column = null)
    {
        $column = $column ?? ($this->model ? $this->model->getKeyName() : 'id');
        $lastId = null;
        do {
            $query = $this->when($lastId, fn($q) => $q->where($column, '>', $lastId));
            $results = $query->limit($count)->orderBy($column, 'ASC')->get();
            $countResults = count($results);
            if ($countResults == 0)
                break;
            $lastId = $results[$countResults - 1]->getAttribute($column);
            if ($callback($results) === false)
                return false;
        } while ($countResults == $count);
        return true;
    }

    public function lazy($chunkSize = 100)
    {
        $generator = function () use ($chunkSize) {
            $this->chunk($chunkSize, function ($results) use (&$generator) {
                foreach ($results as $r) {
                    yield $r;
                }
            });
        };
        return $generator();
    }

    public function lazyById($chunkSize = 100, $column = null)
    {
        $generator = function () use ($chunkSize, $column) {
            $this->chunkById($chunkSize, function ($results) use (&$generator) {
                foreach ($results as $r) {
                    yield $r;
                }
            }, $column);
        };
        return $generator();
    }

    public function paginate(int $perPage = 15, int $page = 1)
    {
        $page = $page > 0 ? $page : 1;
        $total = $this->count();
        $results = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function simplePaginate(int $perPage = 15, int $page = 1)
    {
        $page = $page > 0 ? $page : 1;
        $results = $this->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return [
            'data' => $results,
            'per_page' => $perPage,
            'current_page' => $page,
            'next_page' => count($results) == $perPage ? $page + 1 : null,
        ];
    }

    public function cursorPaginate(int $perPage = 15, ?string $cursor = null, ?string $column = null)
    {
        $column = $column ?? ($this->model ? $this->model->getKeyName() : 'id');
        if ($cursor) {
            $this->where($column, '>', $cursor);
        }
        $results = $this->limit($perPage + 1)->orderBy($column, 'ASC')->get();
        $nextCursor = null;
        if (count($results) > $perPage) {
            $last = array_pop($results);
            $nextCursor = $last->getAttribute($column);
        }
        return ['data' => $results, 'next_cursor' => $nextCursor];
    }

    public function forPage(int $page, int $perPage)
    {
        $page = $page < 1 ? 1 : $page;
        return $this->limit($perPage)->offset(($page - 1) * $perPage);
    }

    public function forPageBeforeId(int $perPage, $id, ?string $column = null)
    {
        $column = $column ?? ($this->model ? $this->model->getKeyName() : 'id');
        return $this->where($column, '<', $id)->orderBy($column, 'DESC')->limit($perPage);
    }

    public function forPageAfterId(int $perPage, $id, ?string $column = null)
    {
        $column = $column ?? ($this->model ? $this->model->getKeyName() : 'id');
        return $this->where($column, '>', $id)->orderBy($column, 'ASC')->limit($perPage);
    }

    public function exists(): bool
    {
        $clone = clone $this;
        $clone->limit(1);
        return !is_null($clone->first());
    }

    // soft delete helpers
    public function withTrashed()
    {
        // remove any deleted_at = null where added by model
        $this->wheres = array_filter($this->wheres, function ($w) {
            return !(
                $w['type'] === 'basic' &&
                strpos($w['column'], 'deleted_at') !== false &&
                $w['operator'] === '=' &&
                is_null($w['value'])
            );
        });
        return $this;
    }

    public function onlyTrashed()
    {
        $this->withTrashed();
        $this->where($this->model->getTable() . '.deleted_at', '!=', null);
        return $this;
    }

    public function withoutTrashed()
    {
        // ensure deleted_at null present
        if ($this->model && $this->model->softDeletes) {
            $this->where($this->model->getTable() . '.deleted_at', '=', null);
        }
        return $this;
    }

    public function doesntExist(): bool
    {
        return !$this->exists();
    }


    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }
    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }
    public function take(int $count)
    {
        return $this->limit($count);
    }

    public function with(array $relations)
    {
        $this->withRelations = array_merge($this->withRelations, $relations);
        return $this;
    }

    public function withCount(array $relations)
    {
        $this->withCounts = array_merge($this->withCounts, $relations);
        return $this;
    }

    public function withAggregate(array $relations, string $function, string $column)
    {
        foreach ($relations as $relation) {
            $this->withAggregates[] = [
                'relation' => $relation,
                'function' => $function,
                'column' => $column
            ];
        }
        return $this;
    }

    public function lockForUpdate()
    {
        $this->lock = "FOR UPDATE";
        return $this;
    }
    public function sharedLock()
    {
        $this->lock = "LOCK IN SHARE MODE";
        return $this;
    }

    public function remember(int $seconds, ?string $key = null)
    {
        $this->cacheTtl = $seconds;
        $this->cacheKey = $key;
        return $this;
    }

    // ========================================================
    //  AGGREGATES (max, min, avg, sum)
    // ========================================================

    /**
     * Get aggregate value
     */
    public function aggregate(string $function, string $column = '*'): mixed
    {
        $col = $column === '*' ? '*' : $this->wrapColumn($column);
        $clone = clone $this;
        $clone->columns = "{$function}({$col}) as aggregate";

        [$sql, $bindings] = $clone->toSql();
        $sql = preg_replace('/ ORDER BY .*$/i', '', $sql);
        $sql = preg_replace('/ LIMIT .*$/i', '', $sql);

        $this->db->query($sql);
        foreach ($bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $this->db->execute();
        $result = $this->db->single();
        return $result['aggregate'] ?? null;
    }

    public function max(string $column): mixed
    {
        return $this->aggregate('MAX', $column);
    }

    public function min(string $column): mixed
    {
        return $this->aggregate('MIN', $column);
    }

    public function avg(string $column): mixed
    {
        $val = $this->aggregate('AVG', $column);
        return $val !== null ? (float) $val : null;
    }

    public function average(string $column): mixed
    {
        return $this->avg($column);
    }

    public function sum(string $column): mixed
    {
        $val = $this->aggregate('SUM', $column);
        return $val !== null ? (float) $val : 0;
    }

    // ========================================================
    //  INCREMENT / DECREMENT
    // ========================================================

    public function increment(string $column, int|float $amount = 1, array $extra = []): int
    {
        $col = $this->wrapColumn($column);
        $sets = ["{$col} = {$col} + :_inc_amount_"];
        $bindings = [':_inc_amount_' => $amount];

        $i = 0;
        foreach ($extra as $key => $value) {
            $param = ":_extra_{$i}_";
            $sets[] = $this->wrapColumn($key) . " = {$param}";
            $bindings[$param] = $value;
            $i++;
        }

        $table = $this->table;
        $setStr = implode(', ', $sets);
        [$whereSql, $whereBindings] = $this->compileWheresOnly();

        $sql = "UPDATE {$table} SET {$setStr}{$whereSql}";
        $allBindings = array_merge($bindings, $whereBindings);

        $this->db->query($sql);
        foreach ($allBindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $this->db->execute();
        return $this->db->rowCount();
    }

    public function decrement(string $column, int|float $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    /**
     * Compile wheres only (without SELECT/FROM) for UPDATE/DELETE
     */
    protected function compileWheresOnly(): array
    {
        if (empty($this->wheres))
            return ['', []];

        [$whereStr, $bindings] = $this->compileWheres();
        return [" WHERE {$whereStr}", $bindings];
    }

    // ========================================================
    //  TRUNCATE
    // ========================================================

    public function truncate(): void
    {
        $this->db->unprepared("TRUNCATE TABLE {$this->table}");
    }

    // ========================================================
    //  OR WHERE VARIANTS
    // ========================================================

    public function orWhereIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR');
    }

    public function orWhereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'OR', true);
    }

    public function orWhereNull(string $column): static
    {
        return $this->whereNull($column, 'OR');
    }

    public function orWhereNotNull(string $column): static
    {
        return $this->whereNotNull($column, 'OR');
    }

    public function orWhereBetween(string $column, array $values): static
    {
        return $this->whereBetween($column, $values, 'OR');
    }

    public function orWhereNotBetween(string $column, array $values): static
    {
        return $this->whereNotBetween($column, $values, 'OR');
    }

    public function orWhereColumn(string $first, string $operator, string $second): static
    {
        return $this->whereColumn($first, $operator, $second, 'OR');
    }

    public function orWhereNot($column, $operator = null, $value = null): static
    {
        return $this->whereNot($column, $operator, $value, 'OR');
    }

    public function orWhereDate($column, $operator, $value = null): static
    {
        return $this->whereDate($column, $operator, $value, 'OR');
    }

    // ========================================================
    //  WHERE LIKE / NOT LIKE (Laravel 11)
    // ========================================================

    public function whereLike(string $column, string $value, bool $caseSensitive = false, string $boolean = 'AND'): static
    {
        $op = $caseSensitive ? 'LIKE BINARY' : 'LIKE';
        $this->wheres[] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $op,
            'value' => $value,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function orWhereLike(string $column, string $value, bool $caseSensitive = false): static
    {
        return $this->whereLike($column, $value, $caseSensitive, 'OR');
    }

    public function whereNotLike(string $column, string $value, bool $caseSensitive = false, string $boolean = 'AND'): static
    {
        $op = $caseSensitive ? 'NOT LIKE BINARY' : 'NOT LIKE';
        $this->wheres[] = [
            'type' => 'Basic',
            'column' => $column,
            'operator' => $op,
            'value' => $value,
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function orWhereNotLike(string $column, string $value, bool $caseSensitive = false): static
    {
        return $this->whereNotLike($column, $value, $caseSensitive, 'OR');
    }

    // ========================================================
    //  WHERE EXISTS
    // ========================================================

    public function whereExists(\Closure $callback, string $boolean = 'AND', bool $not = false): static
    {
        $subQuery = new self($this->db);
        $callback($subQuery);
        [$subSql] = $subQuery->toSql();
        $keyword = $not ? 'NOT EXISTS' : 'EXISTS';
        $this->wheres[] = [
            'type' => 'Raw',
            'sql' => "{$keyword} ({$subSql})",
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function whereNotExists(\Closure $callback, string $boolean = 'AND'): static
    {
        return $this->whereExists($callback, $boolean, true);
    }

    // ========================================================
    //  WHERE FULL TEXT
    // ========================================================

    public function whereFullText(array $columns, string $value, array $options = [], string $boolean = 'AND'): static
    {
        $cols = implode(', ', array_map(fn($c) => $this->wrapColumn($c), $columns));
        $mode = ($options['mode'] ?? '') === 'boolean' ? ' IN BOOLEAN MODE' : '';
        $this->wheres[] = [
            'type' => 'Raw',
            'sql' => "MATCH({$cols}) AGAINST(:_ft_val_{$boolean}",
            'boolean' => $boolean,
        ];
        // Simplified — store as raw where
        $paramName = ':_ft_' . count($this->wheres);
        $this->wheres[array_key_last($this->wheres)] = [
            'type' => 'Raw',
            'sql' => "MATCH({$cols}) AGAINST({$paramName}{$mode})",
            'boolean' => $boolean,
            'bindings' => [$paramName => $value],
        ];
        return $this;
    }

    public function orWhereFullText(array $columns, string $value, array $options = []): static
    {
        return $this->whereFullText($columns, $value, $options, 'OR');
    }

    // ========================================================
    //  WHERE BETWEEN COLUMNS
    // ========================================================

    public function whereBetweenColumns(string $column, array $columns, string $boolean = 'AND', bool $not = false): static
    {
        $col = $this->wrapColumn($column);
        $min = $this->wrapColumn($columns[0]);
        $max = $this->wrapColumn($columns[1]);
        $keyword = $not ? 'NOT BETWEEN' : 'BETWEEN';
        $this->wheres[] = [
            'type' => 'Raw',
            'sql' => "{$col} {$keyword} {$min} AND {$max}",
            'boolean' => $boolean,
        ];
        return $this;
    }

    public function whereNotBetweenColumns(string $column, array $columns, string $boolean = 'AND'): static
    {
        return $this->whereBetweenColumns($column, $columns, $boolean, true);
    }

    // ========================================================
    //  JOIN SUBQUERY
    // ========================================================

    public function joinSub($query, string $alias, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        if ($query instanceof self) {
            [$subSql] = $query->toSql();
        } elseif ($query instanceof \Closure) {
            $sub = new self($this->db);
            $query($sub);
            [$subSql] = $sub->toSql();
        } else {
            $subSql = (string) $query;
        }

        $this->joins[] = "{$type} JOIN ({$subSql}) AS {$alias} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function leftJoinSub($query, string $alias, string $first, string $operator, string $second): static
    {
        return $this->joinSub($query, $alias, $first, $operator, $second, 'LEFT');
    }

    public function rightJoinSub($query, string $alias, string $first, string $operator, string $second): static
    {
        return $this->joinSub($query, $alias, $first, $operator, $second, 'RIGHT');
    }

    // ========================================================
    //  SELECT SUBQUERY & GROUP BY RAW
    // ========================================================

    public function selectSub($query, string $alias): static
    {
        if ($query instanceof self) {
            [$subSql] = $query->toSql();
        } elseif ($query instanceof \Closure) {
            $sub = new self($this->db);
            $query($sub);
            [$subSql] = $sub->toSql();
        } else {
            $subSql = (string) $query;
        }

        return $this->addSelect(new Raw("({$subSql}) as {$alias}"));
    }

    public function groupByRaw(string $expression): static
    {
        $this->groupBy[] = $expression;
        return $this;
    }

    public function search(array|string $columns, string $value): static
    {
        $columns = is_array($columns) ? $columns : [$columns];
        return $this->whereNested(function ($q) use ($columns, $value) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'LIKE', "%{$value}%");
            }
        });
    }

    // ========================================================
    //  EAGER LOAD AGGREGATES
    // ========================================================

    public function withSum(array $relations, string $column): static
    {
        return $this->withAggregate($relations, 'SUM', $column);
    }

    public function withAvg(array $relations, string $column): static
    {
        return $this->withAggregate($relations, 'AVG', $column);
    }

    public function withMin(array $relations, string $column): static
    {
        return $this->withAggregate($relations, 'MIN', $column);
    }

    public function withMax(array $relations, string $column): static
    {
        return $this->withAggregate($relations, 'MAX', $column);
    }

    public function withExists(array $relations): static
    {
        return $this->withAggregate($relations, 'EXISTS', '*');
    }

    // ========================================================
    //  UTILITY METHODS
    // ========================================================

    public function unless($value, callable $callback, ?callable $default = null): static
    {
        if (!$value) {
            $callback($this, $value);
        } elseif ($default) {
            $default($this, $value);
        }
        return $this;
    }

    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function cloneQuery(): static
    {
        return clone $this;
    }

    public function newQuery(): static
    {
        $qb = new static($this->db);
        $qb->table = $this->table;
        $qb->model = $this->model;
        return $qb;
    }

    // ========================================================
    //  DEBUGGING
    // ========================================================

    public function dd(): never
    {
        [$sql, $bindings] = $this->toSql();
        $rawSql = $this->toRawSql();
        echo "SQL: {$sql}\n";
        echo "Raw: {$rawSql}\n";
        echo "Bindings: " . print_r($bindings, true) . "\n";
        exit(1);
    }

    public function dump(): static
    {
        [$sql, $bindings] = $this->toSql();
        $rawSql = $this->toRawSql();
        echo "SQL: {$sql}\n";
        echo "Raw: {$rawSql}\n";
        echo "Bindings: " . print_r($bindings, true) . "\n";
        return $this;
    }

    public function explain(): array
    {
        [$sql, $bindings] = $this->toSql();
        $this->db->query("EXPLAIN {$sql}");
        foreach ($bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $this->db->execute();
        return $this->db->resultSet();
    }

    // ========================================================
    //  CURSOR (Generator — memory efficient)
    // ========================================================

    public function cursor(): \Generator
    {
        [$sql, $bindings] = $this->toSql();
        $this->db->query($sql);
        foreach ($bindings as $param => $value) {
            $this->db->bind($param, $value);
        }
        $this->db->execute();

        while ($row = $this->db->single()) {
            if ($row === false)
                break;
            if ($this->model) {
                $instance = $this->model->newInstance([], true);
                $instance->setRawAttributes((array) $row, true);
                yield $instance;
            } else {
                yield $row;
            }
        }
    }

    /**
     * Iterate each record via chunking
     */
    public function each(callable $callback, int $chunkSize = 100): bool
    {
        return $this->chunk($chunkSize, function ($results) use ($callback) {
            foreach ($results as $key => $value) {
                if ($callback($value, $key) === false) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * Chunk and map each record
     */
    public function chunkMap(callable $callback, int $chunkSize = 100): array
    {
        $results = [];
        $this->chunk($chunkSize, function ($items) use ($callback, &$results) {
            foreach ($items as $item) {
                $results[] = $callback($item);
            }
        });
        return $results;
    }

    /**
     * Lazy by ID descending
     */
    public function lazyByIdDesc($chunkSize = 100, $column = null): \Generator
    {
        $column = $column ?? ($this->model ? $this->model->getKeyName() : 'id');
        $lastId = null;

        while (true) {
            $clone = clone $this;
            if ($lastId !== null) {
                $clone->where($column, '<', $lastId);
            }
            $results = $clone->orderBy($column, 'DESC')->limit($chunkSize)->get();
            if (empty($results))
                break;

            foreach ($results as $result) {
                $lastId = is_array($result) ? ($result[$column] ?? null) : ($result->$column ?? null);
                yield $result;
            }

            if (count($results) < $chunkSize)
                break;
        }
    }

    public function __call($method, $parameters)
    {
        if ($this->model && method_exists($this->model, 'scope' . ucfirst($method))) {
            return $this->model->{'scope' . ucfirst($method)}($this, ...$parameters);
        }
        throw new \BadMethodCallException("Method {$method} does not exist on QueryBuilder.");
    }
}

class Raw
{
    protected $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function __toString()
    {
        return (string) $this->value;
    }
}