<?php

namespace TheFramework\App;

use ReflectionClass;
use Exception;

abstract class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $db;
    protected $builder;

    /** 🔹 Relasi eager loading */
    protected $with = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->builder = (new QueryBuilder($this->db))->setModel($this);

        // otomatis deteksi nama tabel dari nama class model
        if (empty($this->table)) {
            $class = (new ReflectionClass($this))->getShortName();
            $this->table = strtolower(preg_replace('/Model$/', '', $class));
        }
    }

    /* ==================================================
       🔹 QUERY BUILDER WRAPPER
    ================================================== */

    public function query(): QueryBuilder
    {
        return (new QueryBuilder($this->db))
            ->table($this->table)
            ->setModel($this);
    }

    public function all()
    {
        $results = $this->query()->with($this->with)->get();
        return $this->loadRelations($results, $this->with);
    }

    public function find($id)
    {
        $result = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->first();

        if (!$result) return null;

        return $this->loadRelations([$result], $this->with)[0];
    }

    public function where($column, $value)
    {
        $results = $this->query()
            ->where($column, '=', $value)
            ->get();

        return $this->loadRelations($results, $this->with);
    }

    public function insert(array $data)
    {
        return $this->query()->insert($data);
    }

    public function update(array $data, $id)
    {
        return $this->query()
            ->where($this->primaryKey, '=', $id)
            ->update($data);
    }

    public function delete($id)
    {
        return $this->query()
            ->where($this->primaryKey, '=', $id)
            ->delete();
    }

    public function paginate(int $perPage = 10, int $page = 1)
    {
        return $this->query()->paginate($perPage, $page);
    }

    /* ==================================================
       🔹 RELASI MIRIP LARAVEL
    ================================================== */

    protected function hasMany($related, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?? $this->primaryKey;
        return new Relation('hasMany', $this, $related, $foreignKey, $localKey);
    }

    protected function belongsTo($related, $foreignKey, $ownerKey = 'id')
    {
        return new Relation('belongsTo', $this, $related, $foreignKey, $ownerKey);
    }

    protected function hasOne($related, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?? $this->primaryKey;
        return new Relation('hasOne', $this, $related, $foreignKey, $localKey);
    }

    /** 🔹 Tambahkan eager load relations */
    public function with(array $relations)
    {
        $this->with = $relations;
        return $this;
    }

    /* ==================================================
       🔹 NESTED EAGER LOADING (ala Laravel)
    ================================================== */

    public function loadRelations(array $results, array $relations = [])
    {
        $relations = !empty($relations) ? $relations : $this->with;
        if (empty($relations) || empty($results)) return $results;

        // 🔹 Kelompokkan relasi: sessions.participants.user → [ sessions => [ participants.user ] ]
        $grouped = [];
        foreach ($relations as $relation) {
            if (strpos($relation, '.') !== false) {
                [$rel, $nested] = explode('.', $relation, 2);
                $grouped[$rel][] = $nested;
            } else {
                $grouped[$relation] = [];
            }
        }

        foreach ($grouped as $relation => $nestedRels) {
            if (!method_exists($this, $relation)) {
                throw new \Exception("Relasi $relation tidak ditemukan di " . get_class($this));
            }

            $relationObj = $this->$relation();
            if (!$relationObj instanceof Relation) {
                throw new \Exception("Method relasi '$relation' tidak mengembalikan Relation");
            }

            foreach ($results as &$result) {
                $relatedData = $relationObj->getResults($result);
                if ($relatedData === null) $relatedData = [];

                // 🔹 Jika ada nested, maka load lebih dalam
                if (!empty($nestedRels)) {
                    $relatedModel = new $relationObj->related();
                    $relatedModel->with($nestedRels);
                    $relatedData = $relatedModel->loadRelations(
                        is_array($relatedData) ? $relatedData : [$relatedData],
                        $nestedRels
                    );
                }

                // 🔹 Merge (bukan overwrite)
                if (!isset($result[$relation])) {
                    $result[$relation] = $relatedData;
                } else {
                    if (is_array($result[$relation]) && is_array($relatedData)) {
                        $result[$relation] = array_merge_recursive($result[$relation], $relatedData);
                    } else {
                        $result[$relation] = $relatedData;
                    }
                }
            }
        }

        return $results;
    }

    /* ==================================================
       🔹 HELPER
    ================================================== */

    private function getRelationClass(string $relation)
    {
        $relationObj = $this->$relation();
        if ($relationObj instanceof Relation) {
            return $relationObj->related;
        }
        throw new Exception("Tidak bisa menentukan class model untuk relasi '$relation'");
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new Exception("Property atau relasi '$name' tidak ditemukan di " . get_class($this));
    }
}
