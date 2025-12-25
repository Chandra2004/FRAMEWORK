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

    protected $with = [];

    public function __construct()
    {
        // Lazy initialization - tidak langsung connect ke database
        $this->db = Database::getInstance();
        $this->builder = (new QueryBuilder($this->db))->setModel($this);

        // otomatis deteksi nama tabel dari nama class model
        if (empty($this->table)) {
            $class = (new ReflectionClass($this))->getShortName();
            $this->table = strtolower(preg_replace('/Model$/', '', $class));
        }
    }

    /**
     * Check apakah database tersedia
     */
    protected function requireDatabase(): void
    {
        if (!Database::isEnabled()) {
            throw new \TheFramework\App\DatabaseException(
                "This operation requires a database connection, but database is disabled.",
                500,
                null,
                [],
                [],
                true
            );
        }
        $this->db->ensureConnection(true);
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
        $this->requireDatabase();
        $results = $this->query()->with($this->with)->get();
        return $this->loadRelations($results, $this->with);
    }

    public function find($id)
    {
        $this->requireDatabase();
        $result = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->first();

        if (!$result)
            return null;

        return $this->loadRelations([$result], $this->with)[0];
    }

    public function where($column, $value)
    {
        $this->requireDatabase();
        $results = $this->query()
            ->where($column, '=', $value)
            ->get();

        return $this->loadRelations($results, $this->with);
    }

    public function insert(array $data)
    {
        $this->requireDatabase();
        return $this->query()->insert($data);
    }

    public function update(array $data, $id)
    {
        $this->requireDatabase();
        return $this->query()
            ->where($this->primaryKey, '=', $id)
            ->update($data);
    }

    public function delete($id)
    {
        $this->requireDatabase();
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

    protected function belongsToMany($related, $pivotTable, $foreignKey, $relatedKey, $additionalPivotColumns = [])
    {
        return new Relation('belongsToMany', $this, $related, $pivotTable, $foreignKey, $relatedKey, $additionalPivotColumns);
    }

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
        if (empty($relations) || empty($results))
            return $results;

        // Normalisasi format array relation
        $normalizedRelations = [];
        foreach ($relations as $key => $value) {
            $name = is_numeric($key) ? $value : $key;
            $closure = is_numeric($key) ? null : ($value instanceof \Closure ? $value : null);

            // Handle nested "posts.comments"
            if (is_string($name) && strpos($name, '.') !== false) {
                [$root, $child] = explode('.', $name, 2);
                $normalizedRelations[$root]['nested'][] = $child;
                $normalizedRelations[$root]['closure'] = $closure;
            } else {
                $normalizedRelations[$name]['nested'] = [];
                $normalizedRelations[$name]['closure'] = $closure;
            }
        }

        // Proses setiap relasi root
        foreach ($normalizedRelations as $relationName => $options) {
            if (!method_exists($this, $relationName)) {
                // Skip jika method tidak ada (biar aman)
                continue;
            }

            // Ambil objek Relation dari method model (panggil $this->user())
            $relationObj = $this->$relationName();
            if (!$relationObj instanceof Relation)
                continue;

            // 1. Siapkan Query Constraints (WHERE foreign_key IN (...))
            $query = $relationObj->addEagerConstraints($results);

            // 2. Terapkan Closure (jika ada custom select/where user)
            if ($options['closure']) {
                ($options['closure'])($query);
            }

            // 3. Eksekusi Query (Hanya 1x query untuk semua parent!)
            $relatedResults = $query->get();

            // 4. Jika ada nested relations, load recursively
            if (!empty($options['nested'])) {
                $relatedPrototype = new $relationObj->related();
                $relatedResults = $relatedPrototype->loadRelations($relatedResults, $options['nested']);
            }

            // 5. Pasangkan hasil (Match) ke parent models
            $results = $relationObj->match($results, $relatedResults, $relationName);
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