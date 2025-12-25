<?php
namespace TheFramework\App;

class Relation
{
    public $type;
    public $parent;
    public $related;
    public $foreignKey;
    public $localKey;
    public $select = [];
    public $pivotTable;
    public $relatedKey;
    public $additionalPivotColumns = [];

    public function __construct($type, $parent, $related, $foreignKey, $localKey = null, $pivotTable = null, $relatedKey = null, $additionalPivotColumns = [])
    {
        $this->type = $type;
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->pivotTable = $pivotTable;
        $this->relatedKey = $relatedKey;
        $this->additionalPivotColumns = $additionalPivotColumns;
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
                $keyVal = is_object($result) ? $result->{$this->localKey} : $result[$this->localKey];
            }

            if (!is_null($keyVal)) {
                $dictionary[$keyVal][] = $result;
            }
        }
        return $dictionary;
    }
}
