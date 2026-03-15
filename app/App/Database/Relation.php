<?php
namespace TheFramework\App\Database;

use Exception;

/**
 * @mixin QueryBuilder
 */
class Relation
{
    public $query;
    public $parent;
    public ?Model $related;
    public $type;
    public $foreignKey;
    public $localKey;
    public $pivotTable;
    public $relatedKey;
    public $morphType;
    public $select = [];
    public $additionalPivotColumns = [];
    public array $extra = [];
    protected $partitionLimit = null;

    // Pivot options
    protected array $pivotColumns = [];
    protected bool $pivotTimestamps = false;
    protected array $pivotWheres = [];
    protected ?string $pivotOrderBy = null;
    protected ?string $pivotAlias = null;

    // Default model for belongsTo
    protected $withDefault = false;
    protected array $defaultAttributes = [];

    public function __construct(
        $type,
        Model $parent,
        ?Model $related,
        $foreignKey,
        $localKey = null,
        $pivotTable = null,
        $relatedKey = null,
        $additionalPivotColumns = [],
        array $extra = []
    ) {
        $this->type = $type;
        $this->parent = $parent;
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->pivotTable = $pivotTable;
        $this->relatedKey = $relatedKey;
        $this->additionalPivotColumns = $additionalPivotColumns;
        $this->extra = $extra;

        if ($this->related) {
            $this->query = $this->related->query();
        }

        if ($parent->exists) {
            $this->addConstraints();
        }
    }

    public function ofEach(int $count): self
    {
        if ($count < 1) {
            throw new \InvalidArgumentException("Jumlah untuk ofEach() harus lebih besar dari 0.");
        }
        $this->partitionLimit = $count;
        return $this;
    }

    public function getPartitionLimit(): ?int
    {
        return $this->partitionLimit;
    }

    public function getForeignKeyName(): string
    {
        if (in_array($this->type, ['morphMany', 'morphOne'])) {
            return $this->foreignKey;
        }
        return $this->related ? $this->related->getTable() . '.' . $this->foreignKey : $this->foreignKey;
    }

    public function getLocalKeyName(): string
    {
        return $this->localKey;
    }

    public function getQualifiedForeignKeyName()
    {
        return $this->parent->getTable() . '.' . $this->foreignKey;
    }

    public function getRelated(): ?Model
    {
        return $this->related;
    }

    public function getMorphType()
    {
        return $this->localKey;
    }

    public function isMorphTo()
    {
        return $this->type === 'morphTo';
    }

    // ========================================================
    //  ADD CONSTRAINTS
    // ========================================================

    public function addConstraints()
    {
        switch ($this->type) {
            case 'hasMany':
            case 'hasOne':
                $this->query->where($this->foreignKey, '=', $this->parent->getAttribute($this->localKey));
                break;

            case 'belongsTo':
                $this->query->where($this->relatedKey, '=', $this->parent->getAttribute($this->foreignKey));
                break;

            case 'belongsToMany':
                $relatedTable = $this->related->getTable();
                $pivot = $this->pivotTable;
                $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}"]);
                $this->query->table($relatedTable)
                    ->join($pivot, "{$pivot}.{$this->relatedKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
                    ->where("{$pivot}.{$this->foreignKey}", '=', $this->parent->getAttribute($this->localKey));
                $this->applyPivotConstraints();
                break;

            case 'morphMany':
            case 'morphOne':
                $this->query->where($this->foreignKey, '=', $this->parent->getKey());
                $this->query->where($this->relatedKey, '=', get_class($this->parent));
                break;

            case 'hasOneThrough':
            case 'hasManyThrough':
                $this->addThroughConstraints();
                break;

            case 'morphToMany':
                $this->addMorphToManyConstraints();
                break;

            case 'morphedByMany':
                $this->addMorphedByManyConstraints();
                break;
        }
    }

    protected function addThroughConstraints(): void
    {
        $through = $this->extra['through'];
        $throughTable = $through->getTable();
        $relatedTable = $this->related->getTable();
        $firstKey = $this->foreignKey;
        $secondKey = $this->extra['secondKey'];
        $secondLocalKey = $this->extra['secondLocalKey'];

        $this->query->table($relatedTable)
            ->join($throughTable, "{$throughTable}.{$secondLocalKey}", '=', "{$relatedTable}.{$secondKey}")
            ->where("{$throughTable}.{$firstKey}", '=', $this->parent->getAttribute($this->localKey));
    }

    protected function addMorphToManyConstraints(): void
    {
        $pivot = $this->pivotTable;
        $relatedTable = $this->related->getTable();
        $morphType = $this->extra['morphType'];

        $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}", "{$pivot}.{$morphType}"]);
        $this->query->table($relatedTable)
            ->join($pivot, "{$pivot}.{$this->relatedKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
            ->where("{$pivot}.{$this->foreignKey}", '=', $this->parent->getAttribute($this->localKey))
            ->where("{$pivot}.{$morphType}", '=', $this->extra['morphClass']);
    }

    protected function addMorphedByManyConstraints(): void
    {
        $pivot = $this->pivotTable;
        $relatedTable = $this->related->getTable();
        $morphType = $this->extra['morphType'];

        $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}", "{$pivot}.{$morphType}"]);
        $this->query->table($relatedTable)
            ->join($pivot, "{$pivot}.{$this->foreignKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
            ->where("{$pivot}.{$this->relatedKey}", '=', $this->parent->getAttribute($this->localKey))
            ->where("{$pivot}.{$morphType}", '=', $this->extra['morphClass']);
    }

    // ========================================================
    //  EAGER LOADING CONSTRAINTS
    // ========================================================

    public function addEagerConstraints(array $models)
    {
        if ($this->isMorphTo())
            return;

        // Through relations
        if (in_array($this->type, ['hasOneThrough', 'hasManyThrough'])) {
            $through = $this->extra['through'];
            $throughTable = $through->getTable();
            $relatedTable = $this->related->getTable();
            $keys = $this->getKeys($models, $this->localKey);

            $this->query->table($relatedTable)
                ->join($throughTable, "{$throughTable}.{$this->extra['secondLocalKey']}", '=', "{$relatedTable}.{$this->extra['secondKey']}")
                ->whereIn("{$throughTable}.{$this->foreignKey}", $keys);
            return $this->query;
        }

        if ($this->type === 'belongsToMany') {
            $keys = $this->getKeys($models, $this->localKey);
            $pivot = $this->pivotTable;
            $relatedTable = $this->related->getTable();
            $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}"]);
            $this->query->table($relatedTable)
                ->join($pivot, "{$pivot}.{$this->relatedKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
                ->whereIn("{$pivot}.{$this->foreignKey}", $keys);
            $this->applyPivotConstraints();
            return $this->query;
        }

        if (in_array($this->type, ['morphToMany', 'morphedByMany'])) {
            $keys = $this->getKeys($models, $this->localKey);
            $pivot = $this->pivotTable;
            $relatedTable = $this->related->getTable();
            $morphType = $this->extra['morphType'];
            $morphClass = $this->extra['morphClass'];

            if ($this->type === 'morphToMany') {
                $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}", "{$pivot}.{$morphType}"]);
                $this->query->table($relatedTable)
                    ->join($pivot, "{$pivot}.{$this->relatedKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
                    ->whereIn("{$pivot}.{$this->foreignKey}", $keys)
                    ->where("{$pivot}.{$morphType}", '=', $morphClass);
            } else {
                $this->query->select(["{$relatedTable}.*", "{$pivot}.{$this->foreignKey}", "{$pivot}.{$this->relatedKey}", "{$pivot}.{$morphType}"]);
                $this->query->table($relatedTable)
                    ->join($pivot, "{$pivot}.{$this->foreignKey}", '=', "{$relatedTable}.{$this->related->getKeyName()}")
                    ->whereIn("{$pivot}.{$this->relatedKey}", $keys)
                    ->where("{$pivot}.{$morphType}", '=', $morphClass);
            }
            return $this->query;
        }

        $keyType = in_array($this->type, ['hasMany', 'hasOne', 'morphMany', 'morphOne'])
            ? $this->localKey : $this->foreignKey;
        $queryKey = in_array($this->type, ['hasMany', 'hasOne', 'morphMany', 'morphOne'])
            ? $this->foreignKey : $this->relatedKey;

        $keys = $this->getKeys($models, $keyType);
        $this->query->whereIn($queryKey, $keys);

        if (in_array($this->type, ['morphMany', 'morphOne'])) {
            $this->query->where($this->relatedKey, get_class($this->parent));
        }

        return $this->query;
    }

    // ========================================================
    //  MATCH
    // ========================================================

    public function match(array $models, $results, string $relationName)
    {
        $dictionary = $this->buildDictionary($results);
        $relationKey = in_array($this->type, ['hasMany', 'hasOne', 'morphMany', 'morphOne', 'belongsToMany', 'morphToMany', 'morphedByMany', 'hasOneThrough', 'hasManyThrough'])
            ? $this->localKey : $this->foreignKey;

        foreach ($models as $model) {
            // Ensure model is an instance
            if (!$model instanceof Model) {
                continue;
            }

            $key = $model->getAttribute($relationKey);

            if (isset($dictionary[$key])) {
                $value = $dictionary[$key];
                if (in_array($this->type, ['hasOne', 'belongsTo', 'morphOne', 'hasOneThrough'])) {
                    $model->setRelation($relationName, $value[0] ?? null);
                } else {
                    $model->setRelation($relationName, $this->related->newCollection($value));
                }
            } else {
                if (in_array($this->type, ['hasMany', 'belongsToMany', 'morphMany', 'morphToMany', 'morphedByMany', 'hasManyThrough'])) {
                    $model->setRelation($relationName, $this->related->newCollection());
                } elseif ($this->type === 'belongsTo' && $this->withDefault) {
                    $default = $this->related->newInstance($this->defaultAttributes);
                    $model->setRelation($relationName, $default);
                } else {
                    $model->setRelation($relationName, null);
                }
            }
        }
        return $models;
    }

    protected function buildDictionary($results)
    {
        $dictionary = [];

        if (in_array($this->type, ['hasOneThrough', 'hasManyThrough'])) {
            $keyName = $this->foreignKey;
        } elseif (in_array($this->type, ['hasMany', 'hasOne', 'morphMany', 'morphOne'])) {
            $keyName = $this->foreignKey;
        } elseif (in_array($this->type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
            $keyName = $this->foreignKey;
        } else {
            $keyName = $this->relatedKey;
        }

        foreach ($results as $result) {
            // Ensure result is a model instance if we have a related model
            if ($this->related && !$result instanceof Model) {
                // If it's an array/object, try to hydrate it?
                // But QueryBuilder->get() should have done this.
                // Just for safety:
                if (is_array($result) || is_object($result)) {
                   $result = $this->related->newInstance((array)$result, true);
                } else {
                    continue; // Skip if it's something weird like a string
                }
            }

            $key = $result instanceof Model ? $result->getAttribute($keyName) : ($result[$keyName] ?? null);
            
            if ($key !== null) {
                $dictionary[$key][] = $result;
            }
        }
        return $dictionary;
    }

    protected function getKeys(array $models, $keyName)
    {
        $keys = [];
        foreach ($models as $model) {
            $val = $model->getAttribute($keyName);
            if ($val !== null) {
                $keys[] = $val;
            }
        }
        return array_unique($keys);
    }

    // ========================================================
    //  GET RESULTS
    // ========================================================

    public function getResults()
    {
        if ($this->isMorphTo()) {
            $type = $this->parent->{$this->localKey};
            $id = $this->parent->{$this->foreignKey};
            if (!$type || !$id) {
                return $this->withDefault ? $this->related?->newInstance($this->defaultAttributes) : null;
            }
            return (new $type)->find($id);
        }

        if (in_array($this->type, ['hasOne', 'belongsTo', 'morphOne', 'hasOneThrough'])) {
            $result = $this->first();
            if ($result === null && $this->withDefault) {
                return $this->related->newInstance($this->defaultAttributes);
            }
            return $result;
        }

        return $this->get();
    }

    // ========================================================
    //  CREATE / SAVE / SAVE MANY
    // ========================================================

    public function create(array $attributes = [])
    {
        if (!$this->related && $this->type !== 'belongsToMany')
            throw new Exception("Cannot create on a polymorphic relationship.");

        switch ($this->type) {
            case 'hasMany':
            case 'hasOne':
                $attributes[$this->foreignKey] = $this->parent->getAttribute($this->localKey);
                return $this->related->create($attributes);

            case 'morphMany':
            case 'morphOne':
                $attributes[$this->foreignKey] = $this->parent->getKey();
                $attributes[$this->relatedKey] = get_class($this->parent);
                return $this->related->create($attributes);

            case 'belongsToMany':
                $new = $this->related->create($attributes);
                $this->attach($new->getKey());
                return $new;
        }
    }

    public function createMany(array $records): array
    {
        $created = [];
        foreach ($records as $attrs) {
            $created[] = $this->create($attrs);
        }
        return $created;
    }

    /**
     * Save an existing model instance through the relation
     */
    public function save(Model $model): Model
    {
        if (in_array($this->type, ['hasOne', 'hasMany'])) {
            $model->setAttribute($this->foreignKey, $this->parent->getAttribute($this->localKey));
        } elseif (in_array($this->type, ['morphOne', 'morphMany'])) {
            $model->setAttribute($this->foreignKey, $this->parent->getKey());
            $model->setAttribute($this->relatedKey, get_class($this->parent));
        }

        $model->save();
        return $model;
    }

    /**
     * Save multiple model instances
     */
    public function saveMany(array $models): array
    {
        foreach ($models as $model) {
            $this->save($model);
        }
        return $models;
    }

    // ========================================================
    //  BELONGS TO: ASSOCIATE / DISSOCIATE
    // ========================================================

    public function associate(Model $model, string $relationName = ''): Model
    {
        if ($this->type !== 'belongsTo') {
            throw new Exception('associate() only available on belongsTo relations.');
        }
        $this->parent->setAttribute($this->foreignKey, $model->getKey());
        if ($relationName) {
            $this->parent->setRelation($relationName, $model);
        }
        return $this->parent;
    }

    public function dissociate(): Model
    {
        if ($this->type !== 'belongsTo') {
            throw new Exception('dissociate() only available on belongsTo relations.');
        }
        $this->parent->setAttribute($this->foreignKey, null);
        return $this->parent;
    }

    // ========================================================
    //  WITH DEFAULT (untuk belongsTo)
    // ========================================================

    public function withDefault($attributes = true): self
    {
        $this->withDefault = true;
        if (is_array($attributes)) {
            $this->defaultAttributes = $attributes;
        }
        return $this;
    }

    // ========================================================
    //  OF MANY (Has One of Many pattern)
    // ========================================================

    public function latestOfMany(string $column = 'id'): self
    {
        return $this->ofMany($column, 'max');
    }

    public function oldestOfMany(string $column = 'id'): self
    {
        return $this->ofMany($column, 'min');
    }

    public function ofMany($column = 'id', $aggregate = 'max'): self
    {
        $this->type = 'hasOne';
        if (is_array($column)) {
            foreach ($column as $col => $func) {
                $this->query->orderBy($col, $func === 'max' ? 'DESC' : 'ASC');
            }
        } else {
            $this->query->orderBy($column, $aggregate === 'max' ? 'DESC' : 'ASC');
        }
        $this->query->limit(1);
        return $this;
    }

    // ========================================================
    //  PIVOT HELPERS
    // ========================================================

    public function withPivot(...$columns): self
    {
        $this->pivotColumns = array_merge($this->pivotColumns, is_array($columns[0] ?? null) ? $columns[0] : $columns);
        return $this;
    }

    public function withTimestamps(): self
    {
        $this->pivotTimestamps = true;
        $this->pivotColumns = array_merge($this->pivotColumns, ['created_at', 'updated_at']);
        return $this;
    }

    public function as(string $accessor): self
    {
        $this->pivotAlias = $accessor;
        return $this;
    }

    public function wherePivot(string $column, $operator, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        $this->pivotWheres[] = [$column, $operator, $value];
        if ($this->query && $this->pivotTable) {
            $this->query->where("{$this->pivotTable}.{$column}", $operator, $value);
        }
        return $this;
    }

    public function wherePivotIn(string $column, array $values): self
    {
        if ($this->query && $this->pivotTable) {
            $this->query->whereIn("{$this->pivotTable}.{$column}", $values);
        }
        return $this;
    }

    public function wherePivotNull(string $column): self
    {
        if ($this->query && $this->pivotTable) {
            $this->query->whereNull("{$this->pivotTable}.{$column}");
        }
        return $this;
    }

    public function orderByPivot(string $column, string $direction = 'ASC'): self
    {
        if ($this->query && $this->pivotTable) {
            $this->query->orderBy("{$this->pivotTable}.{$column}", $direction);
        }
        return $this;
    }

    public function updateExistingPivot($id, array $attributes): int
    {
        if ($this->type !== 'belongsToMany') {
            throw new Exception('updateExistingPivot() only available on belongsToMany relations.');
        }
        $db = Database::getInstance();
        $pivot = $this->pivotTable;
        $parentId = $this->parent->getAttribute($this->localKey);

        $sets = [];
        $bindings = [':pid' => $parentId, ':rid' => $id];
        $i = 0;
        foreach ($attributes as $col => $val) {
            $param = ":upv_{$i}";
            $sets[] = "`{$col}` = {$param}";
            $bindings[$param] = $val;
            $i++;
        }

        $setStr = implode(', ', $sets);
        $db->query("UPDATE `{$pivot}` SET {$setStr} WHERE `{$this->foreignKey}` = :pid AND `{$this->relatedKey}` = :rid");
        foreach ($bindings as $param => $value) {
            $db->bind($param, $value);
        }
        $db->execute();
        return $db->rowCount();
    }

    protected function applyPivotConstraints(): void
    {
        foreach ($this->pivotWheres as [$column, $operator, $value]) {
            $this->query->where("{$this->pivotTable}.{$column}", $operator, $value);
        }
    }

    // ========================================================
    //  PIVOT OPERATIONS
    // ========================================================

    public function attach($ids, array $attributes = [])
    {
        if (!in_array($this->type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
            throw new Exception('attach() only available on many-to-many relations.');
        }
        $db = Database::getInstance();
        $pivot = $this->pivotTable;
        $parentId = $this->parent->getAttribute($this->localKey);

        foreach ((array) $ids as $id) {
            $row = array_merge([
                $this->foreignKey => $parentId,
                $this->relatedKey => $id,
            ], $attributes);

            // Add morph type for polymorphic
            if (isset($this->extra['morphType'])) {
                $row[$this->extra['morphType']] = $this->extra['morphClass'];
            }

            if ($this->pivotTimestamps) {
                $now = date('Y-m-d H:i:s');
                $row['created_at'] = $now;
                $row['updated_at'] = $now;
            }

            $db->insert($pivot, $row);
        }
        return true;
    }

    public function detach($ids = null)
    {
        if (!in_array($this->type, ['belongsToMany', 'morphToMany', 'morphedByMany'])) {
            throw new Exception('detach() only available on many-to-many relations.');
        }
        $db = Database::getInstance();
        $pivot = $this->pivotTable;
        $parentId = $this->parent->getAttribute($this->localKey);

        $morphWhere = '';
        if (isset($this->extra['morphType'])) {
            $morphWhere = " AND `{$this->extra['morphType']}` = :mtype";
        }

        if (is_null($ids)) {
            $sql = "DELETE FROM `{$pivot}` WHERE `{$this->foreignKey}` = :pid{$morphWhere}";
            $db->query($sql);
            $db->bind(':pid', $parentId);
            if (isset($this->extra['morphType'])) {
                $db->bind(':mtype', $this->extra['morphClass']);
            }
            $db->execute();
            return true;
        }

        foreach ((array) $ids as $id) {
            $sql = "DELETE FROM `{$pivot}` WHERE `{$this->foreignKey}` = :pid AND `{$this->relatedKey}` = :rid{$morphWhere}";
            $db->query($sql);
            $db->bind(':pid', $parentId);
            $db->bind(':rid', $id);
            if (isset($this->extra['morphType'])) {
                $db->bind(':mtype', $this->extra['morphClass']);
            }
            $db->execute();
        }
        return true;
    }

    public function sync($ids, $detaching = true)
    {
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $ids = (array) $ids;
            $current = $this->get()->pluck($this->relatedKey)->all();
            
            if ($detaching) {
                $toRemove = array_diff($current, $ids);
                if (!empty($toRemove)) {
                    $this->detach($toRemove);
                }
            }
            
            $toAttach = array_diff($ids, $current);
            if (!empty($toAttach)) {
                $this->attach($toAttach);
            }
            
            $db->commit();
            return true;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function syncWithoutDetaching($ids)
    {
        return $this->sync($ids, false);
    }

    public function syncWithPivotValues(array $ids, array $attributes): bool
    {
        $existing = $this->get()->pluck($this->relatedKey)->all();
        $toRemove = array_diff($existing, $ids);
        if (!empty($toRemove)) {
            $this->detach($toRemove);
        }
        foreach ($ids as $id) {
            $this->attach([$id], $attributes);
        }
        return true;
    }

    public function toggle($ids)
    {
        $existing = $this->get()->pluck($this->relatedKey)->all();
        $attached = [];
        $detached = [];

        foreach ((array) $ids as $id) {
            if (in_array($id, $existing)) {
                $this->detach($id);
                $detached[] = $id;
            } else {
                $this->attach($id);
                $attached[] = $id;
            }
        }
        return ['attached' => $attached, 'detached' => $detached];
    }

    // ========================================================
    //  __call — Proxy to QueryBuilder
    // ========================================================

    public function __call($method, $parameters)
    {
        if (in_array($method, ['attach', 'detach', 'sync', 'toggle', 'syncWithoutDetaching', 'syncWithPivotValues', 'updateExistingPivot'])) {
            return $this->$method(...$parameters);
        }
        if (is_null($this->query))
            throw new Exception('Query is not available for this relation type.');
        $result = $this->query->$method(...$parameters);
        if ($result === $this->query) {
            return $this;
        }
        return $result;
    }
}

