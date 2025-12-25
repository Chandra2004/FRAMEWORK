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

        if (!$result) return null;

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
        if (empty($relations) || empty($results)) return $results;

        $grouped = [];

        foreach ($relations as $key => $relation) {
            if ($relation instanceof \Closure) {
                $relationName = $key;
                $grouped[$relationName] = ['closure' => $relation, 'nested' => []];
                continue;
            }

            // 🔸 Jika array numerik ['modules', 'modules.assessment']
            if (is_string($relation)) {
                if (strpos($relation, '.') !== false) {
                    [$rel, $nested] = explode('.', $relation, 2);
                    $grouped[$rel]['nested'][] = $nested;
                } else {
                    $grouped[$relation]['nested'] = [];
                }
            }
        }

        foreach ($grouped as $relation => $options) {
            if (!method_exists($this, $relation)) {
                throw new \Exception("Relasi '$relation' tidak ditemukan di " . get_class($this));
            }

            $relationObj = $this->$relation();
            if (!$relationObj instanceof Relation) {
                throw new \Exception("Method relasi '$relation' tidak mengembalikan Relation");
            }

            $closure = $options['closure'] ?? null;
            $nestedRels = $options['nested'] ?? [];

            foreach ($results as &$result) {
                $relatedData = $relationObj->getResults($result, $closure);
                if ($relatedData === null) $relatedData = [];

                if (!empty($nestedRels)) {
                    $relatedModel = new $relationObj->related();
                    $relatedModel->with($nestedRels);
                    $relatedData = $relatedModel->loadRelations(
                        is_array($relatedData) ? $relatedData : [$relatedData],
                        $nestedRels
                    );
                }

                $result[$relation] = $relatedData;
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
