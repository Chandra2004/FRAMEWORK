<?php

namespace TheFramework\App;

use ReflectionClass;

abstract class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $db;
    protected $builder;

    // relasi
    protected $relations = [];
    protected $with = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->builder = (new QueryBuilder($this->db))->setModel($this);

        if (empty($this->table)) {
            $class = (new ReflectionClass($this))->getShortName();
            $this->table = strtolower(preg_replace('/Model$/', '', $class));
        }
    }

    /**
     * QueryBuilder Instance
     */
    public function query(): QueryBuilder
    {
        return (new QueryBuilder(Database::getInstance()))
            ->table($this->table)
            ->setModel($this);
    }

    /**
     * Ambil semua data (+ eager load relasi jika ada)
     */
    public function all()
    {
        $results = $this->query()->with($this->with)->get();
        return $this->loadRelations($results);
    }

    /**
     * Find by primary key
     */
    public function find($id)
    {
        $result = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->with($this->with)
            ->first();

        return $result ? $this->loadRelations([$result])[0] : null;
    }

    /**
     * Where sederhana
     */
    public function where($column, $value)
    {
        $results = $this->query()
            ->where($column, '=', $value)
            ->with($this->with)
            ->get();

        return $this->loadRelations($results);
    }

    /**
     * Insert
     */
    public function insert(array $data)
    {
        return $this->query()->insert($data);
    }

    /**
     * Update
     */
    public function update(array $data, $id)
    {
        return $this->query()->where($this->primaryKey, '=', $id)->update($data);
    }

    /**
     * Delete
     */
    public function delete($id)
    {
        return $this->query()->where($this->primaryKey, '=', $id)->delete();
    }

    /**
     * Pagination
     */
    public function paginate(int $perPage = 10, int $page = 1)
    {
        return $this->query()->paginate($perPage, $page);
    }

    /* ==================================================
       🔹 Bagian RELASI ala Laravel
    ================================================== */

    public function hasMany(string $related, string $foreignKey, $localValue)
    {
        $instance = new $related();
        return $instance->query()->where($foreignKey, '=', $localValue);
    }

    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id')
    {
        $instance = new $related();
        return $instance->query()->where($ownerKey, '=', $this->{$foreignKey});
    }

    public function hasOne(string $related, string $foreignKey, string $localKey = 'id')
    {
        $instance = new $related();
        return $instance->query()->where($foreignKey, '=', $this->{$localKey});
    }

    /**
     * Eager Load dengan ->with()
     */
    public function with(array $relations)
    {
        $this->with = $relations;
        return $this;
    }

    /**
     * Loader untuk relasi (mirip eager loading di Laravel)
     */
    private function loadRelations(array $results)
    {
        if (empty($this->with) || empty($results)) return $results;

        foreach ($this->with as $relation) {
            if (!method_exists($this, $relation)) {
                throw new \Exception("Relasi $relation tidak ditemukan di " . get_class($this));
            }

            foreach ($results as &$result) {
                $result[$relation] = $this->{$relation}($result);
            }
        }

        return $results;
    }
}
