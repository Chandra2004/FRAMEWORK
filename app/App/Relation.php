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

    public function getResults($parentRow, $closure = null)
    {
        $relatedModel = new $this->related();
        $query = $relatedModel->query();

        if (!empty($this->select)) {
            $query->select($this->select);
        }

        $localValue = $parentRow[$this->localKey] ?? null;

        if ($localValue === null) {
            return $this->type === 'hasOne' || $this->type === 'belongsTo' ? null : [];
        }

        if ($closure instanceof \Closure) {
            $closure($query);
        }

        switch ($this->type) {
            case 'hasMany':
                return $query->where($this->foreignKey, '=', $localValue)->get();

            case 'hasOne':
                return $query->where($this->foreignKey, '=', $localValue)->first();

            case 'belongsTo':
                $foreignValue = $parentRow[$this->foreignKey] ?? null;
                if ($foreignValue === null) return null;
                return $query->where($this->localKey, '=', $foreignValue)->first();
        }

        return null;
    }
}
