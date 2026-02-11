<?php
namespace TheFramework\App;

class Relation
{
    public $query;
    public $parent;
    public $related;
    public $type;
    public $foreignKey;
    public $localKey;
    public $pivotTable;
    public $relatedKey; // Owner Key for BelongsTo or Related Key for Pivot
    public $select = []; // Kolom yang akan diselect
    public $additionalPivotColumns = [];

    protected static $constraints = true;

    /**
     * @param string $type HasMany, BelongsTo, etc
     * @param Model $parent Parent model instance
     * @param string|Model $related Related model class or instance
     * @param string $foreignKey
     * @param string $localKey
     */
    public function __construct($type, Model $parent, $related, $foreignKey, $localKey = null, $pivotTable = null, $relatedKey = null, $additionalPivotColumns = [])
    {
        $this->type = $type;
        $this->parent = $parent;
        $this->related = is_string($related) ? new $related : $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->pivotTable = $pivotTable;
        $this->relatedKey = $relatedKey;
        $this->additionalPivotColumns = $additionalPivotColumns;

        // Initialize Query Builder for the related model
        $this->query = $this->related->query(); // Asumsi Model punya method query() public/accessible

        // Add constraints only IF parent exists (has ID)
        if ($parent->exists) {
            $this->addConstraints();
        }
    }

    /**
     * Add basic WHERE constraints based on the relation type.
     */
    public function addConstraints()
    {
        if ($this->type === 'hasMany' || $this->type === 'hasOne') {
            $this->query->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey));
        } elseif ($this->type === 'belongsTo') {
            $this->query->where($this->relatedKey, '=', $this->parent->getAttribute($this->foreignKey));
        }
        // TODO: Implement belongsToMany constraints
    }

    /**
     * Execute the query and get results.
     */
    public function getResults()
    {
        if ($this->type === 'hasOne' || $this->type === 'belongsTo') {
            return $this->query->first();
        }
        return $this->query->get();
    }

    /**
     * Execute query as get()
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Execute query as first()
     */
    public function first()
    {
        return $this->query->first();
    }

    /**
     * Create a new instance of the related model.
     * Automatically sets foreign keys.
     */
    public function create(array $attributes = [])
    {
        // Set Foreign Key automatic assignment
        if ($this->type === 'hasMany' || $this->type === 'hasOne') {
            $attributes[$this->foreignKey] = $this->parent->getAttribute($this->localKey);
        }

        return $this->related->create($attributes);
    }

    public function select(array $columns)
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Set constraint query untuk eager loading (WHERE IN)
     */
    public function addEagerConstraints(array $models)
    {
        $relatedModel = new $this->related();
        $query = $relatedModel->query();

        if ($this->type === 'hasMany' || $this->type === 'hasOne') {
            // WHERE foreign_key IN (parent_ids...)
            $keys = $this->getKeys($models, $this->localKey);
            $query->whereIn($this->foreignKey, $keys);
        } elseif ($this->type === 'belongsTo') {
            // WHERE owner_key IN (foreign_keys...)
            $keys = $this->getKeys($models, $this->foreignKey);
            $query->whereIn($this->localKey, $keys);
        }

        if (!empty($this->select)) {
            $query->select($this->select);
        }

        return $query;
    }

    /**
     * Pasangkan hasil query eager load ke parent model
     */
    public function match(array $models, array $results, string $relationName)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as &$model) {
            $key = $this->getModelKey($model);

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];

                // Jika hasOne atau belongsTo, ambil elemen pertama saja
                if ($this->type === 'hasOne' || $this->type === 'belongsTo') {
                    $model[$relationName] = $value[0];
                } else {
                    $model[$relationName] = $value;
                }
            } else {
                // Default value jika tidak ada relasi
                $model[$relationName] = ($this->type === 'hasMany') ? [] : null;
            }
        }

        return $models;
    }

    /**
     * Helper: Ambil semua key unik dari array model untuk WHERE IN
     */
    protected function getKeys(array $models, $keyName)
    {
        $keys = [];
        foreach ($models as $model) {
            $val = is_object($model) ? ($model->$keyName ?? null) : ($model[$keyName] ?? null);
            if (!is_null($val)) {
                $keys[] = $val;
            }
        }
        return array_unique($keys);
    }

    protected function getModelKey($model)
    {
        if ($this->type === 'hasMany' || $this->type === 'hasOne') {
            return is_object($model) ? $model->{$this->localKey} : $model[$this->localKey];
        } elseif ($this->type === 'belongsTo') {
            return is_object($model) ? $model->{$this->foreignKey} : $model[$this->foreignKey];
        }
        return null;
    }

    protected function buildDictionary(array $results)
    {
        $dictionary = [];
        foreach ($results as $result) {
            // Tentukan key pengelompokan
            // Untuk hasMany/hasOne: key di result adalah foreignKey
            // Untuk belongsTo: key di result adalah localKey (ownerKey)

            $keyVal = null;
            if ($this->type === 'hasMany' || $this->type === 'hasOne') {
                $keyVal = is_object($result) ? $result->{$this->foreignKey} : $result[$this->foreignKey];
            } elseif ($this->type === 'belongsTo') {
                $keyVal = is_object($result) ? $result->{$this->localKey} : $result[$this->localKey]; // FIX: localKey relations adalah ownerKey (primary key parent usually)
                // Wait, logic ini membingungkan di Relation.php karena naming localKey/foreignKey sering tertukar.
                // Standard:
                // hasMany: FK ada di Child. LocalKey ada di Parent.
                // belongsTo: FK ada di Parent (this). OwnerKey ada di Child (related).
                // Di Constructor kita, $relatedKey adalah ownerKey untuk belongsTo.
                $keyVal = is_object($result) ? $result->{$this->relatedKey} : $result[$this->relatedKey];
            }

            if (!is_null($keyVal)) {
                $dictionary[$keyVal][] = $result;
            }
        }
        return $dictionary;
    }

    /**
     * Handle dynamic method calls to the relationship.
     */
    public function __call($method, $parameters)
    {
        $result = $this->query->$method(...$parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
}
