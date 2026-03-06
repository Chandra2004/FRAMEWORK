<?php
namespace TheFramework\App\Database;

use ReflectionClass;
use Exception;
use ArrayAccess;
use Closure;

/**
 * @method static \TheFramework\App\Database\QueryBuilder query()
 * @method static array all()
 * @method static mixed find($id)
 * @method static mixed findOrFail($id)
 * @method static static with(array $relations)
 * @method static static withCount(array $relations)
 * @method static \TheFramework\App\Database\QueryBuilder where($column, $operator = null, $value = null, $boolean = 'AND')
 * @method static \TheFramework\App\Database\QueryBuilder whereHas(string $relation, Closure $callback = null)
 * @method static \TheFramework\App\Database\QueryBuilder latest(string $column = 'created_at')
 * @method static \TheFramework\App\Database\QueryBuilder oldest(string $column = 'created_at')
 * @method static int count()
 * @method static array paginate(int $perPage = 15, int $page = 1)
 * @method static mixed create(array $data)
 * @method static int update(array $data)
 * @method static int delete($id = null)
 * @method static mixed firstOrCreate(array $attributes, array $values = [])
 * @method static mixed updateOrCreate(array $attributes, array $values)
 * @method static int upsert(array $values, array $uniqueBy, array $update)
 * @method static \TheFramework\App\Database\QueryBuilder search(array|string $columns, string $value)
 */

abstract class Model implements \JsonSerializable, ArrayAccess
{
    // global scopes stored by name => callable(builder)
    protected static array $globalScopes = [];

    // Event handlers: [event => [callbacks]]
    protected static array $eventHandlers = [];
    protected static array $booted = [];

    protected $table;
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    public $incrementing = true;
    public $exists = false;
    protected $db;

    protected $attributes = [];
    protected $original = [];
    protected $changes = [];
    protected $relations = [];

    protected $with = [];
    protected $fillable = [];
    protected $guarded = ['*'];
    protected $hidden = [];
    protected $visible = [];
    protected $appends = [];
    protected $casts = [];
    protected $timestamps = true;
    protected $softDeletes = false;
    protected $touches = [];
    protected $dateFormat;
    protected $connection;

    // Date columns
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const DELETED_AT = 'deleted_at';

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->bootIfNotBooted();
        $this->fill($attributes);
        $this->syncOriginal();
    }

    protected function bootIfNotBooted(): void
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
            static::booted();
        }
        $this->bootGlobalScopes();
    }

    /**
     * Override in subclass for custom boot logic
     */
    protected static function boot(): void
    {
        // Override in child models
    }

    /**
     * Override in subclass — called after boot()
     */
    protected static function booted(): void
    {
        // Override in child models
    }

    public function fill(array $attributes)
    {
        foreach ($this->filterFillable($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    public function save(array $options = [])
    {
        $query = $this->newQuery();

        // Fire saving event
        if ($this->fireModelEvent('saving') === false)
            return false;

        if ($this->timestamps && ($options['timestamps'] ?? true) !== false) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributes();

        if ($this->exists) {
            // Fire updating event
            if ($this->fireModelEvent('updating') === false)
                return false;

            if (empty($attributes))
                return true;

            $id = $this->getKey();
            $saved = $query->where($this->getKeyName(), $id)->update($attributes) > 0;

            if ($saved) {
                $this->changes = $this->getDirty();
                $this->syncOriginal();
                $this->fireModelEvent('updated');
                $this->touchOwners();
            }
        } else {
            // Fire creating event
            if ($this->fireModelEvent('creating') === false)
                return false;

            if (empty($attributes[$this->getKeyName()]) && !$this->incrementing) {
                $attributes[$this->getKeyName()] = $this->generateKey();
                $this->setAttribute($this->getKeyName(), $attributes[$this->getKeyName()]);
            }

            // Get fresh attributes after UUID generation
            $attributes = $this->getAttributes();

            if (empty($attributes)) {
                error_log("[MODEL SAVE] No attributes to save for model [" . static::class . "].");
                return false;
            }

            // Refactor: Use insert() instead of insertGetId() for non-incrementing models
            // to avoid false-negatives when lastInsertId() returns "0"
            if (!$this->incrementing) {
                $saved = $query->insert($attributes) > 0;
            } else {
                $id = $query->insertGetId($attributes);
                if ($id) {
                    $this->setAttribute($this->getKeyName(), $id);
                    $saved = true;
                } else {
                    $saved = false;
                }
            }

            if ($saved) {
                $this->exists = true;
                $this->syncOriginal();
                $this->fireModelEvent('created');
            } else {
                return false;
            }
        }

        $this->fireModelEvent('saved');
        return $saved ?? false;
    }

    /**
     * Save without firing model events
     */
    public function saveQuietly(array $options = []): bool
    {
        return static::withoutEvents(fn() => $this->save($options));
    }

    public function touch()
    {
        if (!$this->exists) {
            return false;
        }
        $this->updateTimestamps();
        return $this->save();
    }

    public function fresh(array $with = [])
    {
        if (!$this->exists)
            return null;
        return static::query()->with($with)->find($this->getKey());
    }

    public function refresh()
    {
        if (!$this->exists)
            return $this;
        $fresh = static::query()->find($this->getKey());
        $this->setRawAttributes($fresh->getAttributes(), true);
        $this->relations = [];
        return $this;
    }

    protected function updateTimestamps()
    {
        $time = date('Y-m-d H:i:s');
        if (!$this->exists && !isset($this->attributes['created_at'])) {
            $this->setAttribute('created_at', $time);
        }
        $this->setAttribute('updated_at', $time);
    }

    protected function bootGlobalScopes()
    {
        foreach (static::$globalScopes as $scope) {
            if (is_callable($scope)) {
                $scope($this->newQuery());
            }
        }
    }

    /**
     * Tambah global scope (panggil di booting model)
     */
    public static function addGlobalScope(string $name, callable $scope)
    {
        static::$globalScopes[$name] = $scope;
    }

    /**
     * Hapus global scope tertentu dan kembalikan query tanpa scope itu
     */
    public static function withoutGlobalScope(string $name)
    {
        $query = static::query();
        unset(static::$globalScopes[$name]);
        return $query;
    }

    /**
     * Aktifkan kembali semua global scopes
     */
    public static function withoutGlobalScopes()
    {
        static::$globalScopes = [];
        return static::query();
    }

    public function loadRelations(array $models, array $relations)
    {
        if (empty($relations) || empty($models)) {
            return $models;
        }

        $parsed = [];
        foreach ($relations as $name => $constraints) {
            if (is_numeric($name)) {
                $name = (string) $constraints;
            }
            $dot = strpos($name, '.');
            if ($dot !== false) {
                $base = substr($name, 0, $dot);
                $nested = substr($name, $dot + 1);
                if (!isset($parsed[$base])) {
                    $parsed[$base] = [];
                }
                $parsed[$base][] = $nested;
            } else {
                if (!isset($parsed[$name])) {
                    $parsed[$name] = [];
                }
            }
        }

        foreach ($parsed as $name => $nested) {
            $constraints = function () {};

            $relationInstance = $this->{$name}();

            $results = null;

            if ($relationInstance->isMorphTo()) {
                $this->loadMorphTo($models, $name, $constraints);
                $results = [];
            } else {
                $this->loadEagerRelation($models, $name, $constraints);

                $results = [];
                foreach ($models as $model) {
                    $relationValue = $model->getAttribute($name);
                    if ($relationValue instanceof Model) {
                        $results[spl_object_hash($relationValue)] = $relationValue;
                    } elseif (is_iterable($relationValue)) {
                        foreach ($relationValue as $item) {
                            if ($item instanceof Model)
                                $results[spl_object_hash($item)] = $item;
                        }
                    }
                }
            }

            if (!empty($nested) && !empty($results)) {
                $results = array_values($results);
                $firstResult = $results[0];
                $firstResult->loadRelations($results, $nested);
            }
        }

        return $models;
    }

    protected function loadEagerRelation(array $models, string $name, Closure $constraints)
    {
        $relation = $this->{$name}();
        $relation->addEagerConstraints($models);
        $constraints($relation);
        $results = $relation->get();
        $relation->match($models, $results, $name);
    }

    protected function loadMorphTo(array &$models, string $name, Closure $constraints)
    {
        $relation = $this->{$name}();
        $morphType = $relation->getMorphType();
        $morphId = $relation->getForeignKeyName();

        $grouped = [];
        foreach ($models as $model) {
            if ($type = $model->$morphType) {
                $grouped[$type][$model->$morphId][] = $model;
            }
        }

        foreach ($grouped as $type => $ids) {
            $instance = new $type;
            $results = $instance->query()->whereIn($instance->getKeyName(), array_keys($ids))->get();

            foreach ($results as $result) {
                foreach ($ids[$result->getKey()] as $model) {
                    $model->setRelation($name, $result);
                }
            }
        }
    }

    public function loadCounts(array $models, array $relations)
    {
        return $this->loadAggregates($models, $relations, 'count', '*');
    }

    public function loadAggregates(array $models, array $relationData, ?string $defaultFunction = null, ?string $defaultColumn = null)
    {
        if (empty($models)) {
            return $models;
        }

        foreach ($relationData as $data) {
            $relationName = is_array($data) ? $data['relation'] : $data;
            $function = is_array($data) ? ($data['function'] ?? $defaultFunction) : $defaultFunction;
            $column = is_array($data) ? ($data['column'] ?? $defaultColumn) : $defaultColumn;

            $parts = explode(' as ', $relationName);
            $relation = $parts[0];
            $alias = $parts[1] ?? $relation . '_' . $function;

            $relationInstance = $this->{$relation}();

            $qualifiedForeignKey = $relationInstance->getForeignKeyName();
            $foreignKeyParts = explode('.', $qualifiedForeignKey);
            $unqualifiedForeignKey = end($foreignKeyParts);

            $localKey = $relationInstance->getLocalKeyName();

            $modelKeys = array_unique(array_filter(array_map(function ($model) use ($localKey) {
                return $model->getAttribute($localKey);
            }, $models)));

            if (empty($modelKeys)) {
                foreach ($models as $model) {
                    $model->setAttribute($alias, 0);
                }
                continue;
            }

            $results = $relationInstance->getRelated()->query()
                ->selectRaw("{$qualifiedForeignKey}, {$function}({$column}) as aggregate")
                ->whereIn($qualifiedForeignKey, $modelKeys)
                ->groupBy($qualifiedForeignKey)
                ->get();

            $dictionary = [];
            foreach ($results as $result) {
                $key = is_object($result) ? $result->getAttribute($unqualifiedForeignKey) : $result[$unqualifiedForeignKey];
                $value = is_object($result) ? $result->getAttribute('aggregate') : $result['aggregate'];
                $dictionary[$key] = $value;
            }

            foreach ($models as $model) {
                $modelKey = $model->getAttribute($localKey);
                $count = isset($dictionary[$modelKey]) ? (int) $dictionary[$modelKey] : 0;
                $model->setAttribute($alias, $count);
            }
        }

        return $models;
    }

    protected function hasMany($relatedClass, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?? $this->primaryKey;
        return new Relation('hasMany', $this, new $relatedClass, $foreignKey, $localKey);
    }

    protected function belongsTo($relatedClass, $foreignKey, $ownerKey = 'id')
    {
        return new Relation('belongsTo', $this, new $relatedClass, $foreignKey, $foreignKey, null, $ownerKey);
    }

    protected function hasOne($relatedClass, $foreignKey, $localKey = null)
    {
        $localKey = $localKey ?? $this->primaryKey;
        return new Relation('hasOne', $this, new $relatedClass, $foreignKey, $localKey);
    }

    protected function belongsToMany($relatedClass, $pivotTable, $foreignPivotKey, $relatedPivotKey, $localKey = null, $relatedKey = null)
    {
        $localKey = $localKey ?? $this->primaryKey;
        $relatedKey = $relatedKey ?? (new $relatedClass)->getKeyName();
        return new Relation('belongsToMany', $this, new $relatedClass, $foreignPivotKey, $localKey, $pivotTable, $relatedPivotKey, $relatedKey);
    }

    public function morphTo($name = null, $type = null, $id = null)
    {
        $name = $name ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $type = $type ?? $name . '_type';
        $id = $id ?? $name . '_id';
        return new Relation('morphTo', $this, null, $id, $type);
    }

    protected function morphMany($relatedClass, $name)
    {
        $type = $name . '_type';
        $id = $name . '_id';
        return new Relation('morphMany', $this, new $relatedClass, $id, $this->getKeyName(), null, $type);
    }

    // ========================================================
    //  MISSING RELATIONS (morphOne, Through, morphToMany)
    // ========================================================

    protected function morphOne($relatedClass, $name)
    {
        $type = $name . '_type';
        $id = $name . '_id';
        return new Relation('morphOne', $this, new $relatedClass, $id, $this->getKeyName(), null, $type);
    }

    protected function hasOneThrough($relatedClass, $throughClass, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null)
    {
        $through = new $throughClass;
        $related = new $relatedClass;
        $firstKey = $firstKey ?? strtolower((new ReflectionClass($this))->getShortName()) . '_id';
        $secondKey = $secondKey ?? strtolower((new ReflectionClass($through))->getShortName()) . '_id';
        $localKey = $localKey ?? $this->getKeyName();
        $secondLocalKey = $secondLocalKey ?? $through->getKeyName();

        return new Relation('hasOneThrough', $this, $related, $firstKey, $localKey, null, null, [], [
            'through' => $through,
            'secondKey' => $secondKey,
            'secondLocalKey' => $secondLocalKey,
        ]);
    }

    protected function hasManyThrough($relatedClass, $throughClass, $firstKey = null, $secondKey = null, $localKey = null, $secondLocalKey = null)
    {
        $through = new $throughClass;
        $related = new $relatedClass;
        $firstKey = $firstKey ?? strtolower((new ReflectionClass($this))->getShortName()) . '_id';
        $secondKey = $secondKey ?? strtolower((new ReflectionClass($through))->getShortName()) . '_id';
        $localKey = $localKey ?? $this->getKeyName();
        $secondLocalKey = $secondLocalKey ?? $through->getKeyName();

        return new Relation('hasManyThrough', $this, $related, $firstKey, $localKey, null, null, [], [
            'through' => $through,
            'secondKey' => $secondKey,
            'secondLocalKey' => $secondLocalKey,
        ]);
    }

    protected function morphToMany($relatedClass, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $table = $table ?? $name . 's';
        $foreignPivotKey = $foreignPivotKey ?? $name . '_id';
        $relatedPivotKey = $relatedPivotKey ?? strtolower((new ReflectionClass($relatedClass))->getShortName()) . '_id';
        $morphType = $name . '_type';

        return new Relation('morphToMany', $this, new $relatedClass, $foreignPivotKey, $this->getKeyName(), $table, $relatedPivotKey, [], [
            'morphType' => $morphType,
            'morphClass' => static::class,
        ]);
    }

    protected function morphedByMany($relatedClass, $name, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $table = $table ?? $name . 's';
        $foreignPivotKey = $foreignPivotKey ?? $name . '_id';
        $relatedPivotKey = $relatedPivotKey ?? strtolower((new ReflectionClass($relatedClass))->getShortName()) . '_id';
        $morphType = $name . '_type';

        return new Relation('morphedByMany', $this, new $relatedClass, $relatedPivotKey, $this->getKeyName(), $table, $foreignPivotKey, [], [
            'morphType' => $morphType,
            'morphClass' => $relatedClass,
        ]);
    }

    // ========================================================
    //  DELETE / SOFT DELETE / RESTORE
    // ========================================================

    public function delete(): bool
    {
        if (!$this->exists)
            return false;
        if ($this->fireModelEvent('deleting') === false)
            return false;

        if ($this->softDeletes) {
            $this->setAttribute(static::DELETED_AT, date('Y-m-d H:i:s'));
            $saved = $this->newQueryWithoutScopes()
                ->where($this->getKeyName(), $this->getKey())
                ->update([static::DELETED_AT => $this->getAttribute(static::DELETED_AT)]);
            $this->fireModelEvent('trashed');
        } else {
            $saved = $this->newQuery()
                ->where($this->getKeyName(), $this->getKey())
                ->delete();
            $this->exists = false;
        }

        $this->fireModelEvent('deleted');
        return $saved > 0;
    }

    public function deleteQuietly(): bool
    {
        return static::withoutEvents(fn() => $this->delete());
    }

    public function forceDelete(): bool
    {
        if (!$this->exists)
            return false;
        if ($this->fireModelEvent('forceDeleting') === false)
            return false;

        $result = $this->newQueryWithoutScopes()
            ->where($this->getKeyName(), $this->getKey())
            ->delete();

        $this->exists = false;
        $this->fireModelEvent('forceDeleted');
        return $result > 0;
    }

    public function restore(): bool
    {
        if (!$this->softDeletes)
            return false;
        if ($this->fireModelEvent('restoring') === false)
            return false;

        $this->setAttribute(static::DELETED_AT, null);
        $result = $this->newQueryWithoutScopes()
            ->where($this->getKeyName(), $this->getKey())
            ->update([static::DELETED_AT => null]);

        $this->exists = true;
        $this->fireModelEvent('restored');
        return $result > 0;
    }

    public function trashed(): bool
    {
        return $this->softDeletes && !is_null($this->getAttribute(static::DELETED_AT));
    }

    public function replicate(?array $except = null)
    {
        $attributes = $this->attributes;
        unset($attributes[$this->getKeyName()]);
        if ($this->timestamps) {
            unset($attributes[static::CREATED_AT], $attributes[static::UPDATED_AT]);
        }
        if ($this->softDeletes) {
            unset($attributes[static::DELETED_AT]);
        }
        if (!is_null($except)) {
            $attributes = array_diff_key($attributes, array_flip($except));
        }
        $instance = new static($attributes);
        $instance->fireModelEvent('replicating');
        return $instance;
    }

    public static function all()
    {
        return static::query()->get();
    }
    public static function find($id)
    {
        return static::query()->find($id);
    }
    public static function findOrFail($id)
    {
        return static::query()->findOrFail($id);
    }
    public static function with(array $relations)
    {
        return static::query()->with($relations);
    }
    public static function withCount(array $relations)
    {
        return static::query()->withCount($relations);
    }
    public static function where($column, $operator = null, $value = null)
    {
        return static::query()->where(...func_get_args());
    }
    public static function latest(string $column = 'created_at')
    {
        return static::query()->latest($column);
    }
    public static function oldest(string $column = 'created_at')
    {
        return static::query()->oldest($column);
    }

    public static function query(): QueryBuilder
    {
        return (new static)->newQuery();
    }

    public static function create(array $data)
    {
        return static::query()->create($data);
    }

    public static function firstOrCreate(array $attributes, array $values = [])
    {
        return static::query()->firstOrCreate($attributes, $values);
    }

    public static function updateOrCreate(array $attributes, array $values)
    {
        return static::query()->updateOrCreate($attributes, $values);
    }

    public static function destroy($ids)
    {
        return static::query()->destroy($ids);
    }

    public function newQuery(): QueryBuilder
    {
        $query = (new QueryBuilder(Database::getInstance()))->setModel($this);

        if ($this->softDeletes) {
            $query->where($this->getTable() . '.deleted_at', '=', null);
        }

        return $query;
    }

    public function newQueryWithoutScopes(): QueryBuilder
    {
        $query = (new QueryBuilder(Database::getInstance()))->setModel($this);
        return $query;
    }

    public function getTable()
    {
        if (empty($this->table)) {
            $class = new ReflectionClass($this);
            return strtolower($class->getShortName()) . 's';
        }
        return $this->table;
    }

    public function hydrate(array $results): array
    {
        return array_map(function ($result) {
            $model = $this->newInstance((array) $result, true);
            return $model;
        }, $results);
    }

    public function newInstance(array $attributes = [], bool $exists = false)
    {
        $model = new static;
        $model->setRawAttributes((array) $attributes, true);
        $model->exists = $exists; // Set AFTER setRawAttributes to prevent override
        return $model;
    }

    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->exists = true;
            $this->syncOriginal();
        }
        return $this;
    }

    // ========================================================
    //  DIRTY TRACKING
    // ========================================================

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        $this->changes = [];
        return $this;
    }

    public function isDirty($attributes = null): bool
    {
        $dirty = $this->getDirty();
        if (is_null($attributes))
            return count($dirty) > 0;
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        foreach ($attributes as $attr) {
            if (array_key_exists($attr, $dirty))
                return true;
        }
        return false;
    }

    public function isClean($attributes = null): bool
    {
        return !$this->isDirty(...func_get_args());
    }

    public function wasChanged($attributes = null): bool
    {
        if (is_null($attributes))
            return count($this->changes) > 0;
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        foreach ($attributes as $attr) {
            if (array_key_exists($attr, $this->changes))
                return true;
        }
        return false;
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function getOriginal($key = null, $default = null): mixed
    {
        if ($key === null)
            return $this->original;
        return $this->original[$key] ?? $default;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getQualifiedKeyName()
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            $value = $this->attributes[$key];
            if ($this->hasGetMutator($key)) {
                return $this->mutateAttribute($key, $value);
            }
            if (isset($this->casts[$key])) {
                return $this->castAttribute($key, $value);
            }
            return $value;
        }
        if (array_key_exists($key, $this->relations))
            return $this->relations[$key];
        if ($this->hasGetMutator($key))
            return $this->mutateAttribute($key, null);
        if (method_exists($this, $key))
            return $this->getRelationValue($key);
        return null;
    }

    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $this->mutateAttributeForSet($key, $value);
        } else {
            $this->attributes[$key] = $value;
        }
    }

    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    protected function getRelationValue($key)
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        $value = $this->$key();
        if (!$value instanceof Relation)
            return $value;
        $results = $value->getResults();
        return $this->setRelation($key, $results);
    }

    protected function hasGetMutator($key): bool
    {
        return method_exists($this, 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute');
    }

    protected function hasSetMutator($key): bool
    {
        return method_exists($this, 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute');
    }

    protected function mutateAttribute($key, $value)
    {
        return $this->{'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute'}($value);
    }

    protected function mutateAttributeForSet($key, $value)
    {
        $this->{'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute'}($value);
    }

    protected function castAttribute($key, $value)
    {
        if (is_null($value))
            return null;
        switch ($this->casts[$key]) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return json_decode($value);
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'date':
                return \DateTime::createFromFormat('Y-m-d', $value)->setTime(0, 0, 0);
            case 'datetime':
                return \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            default:
                return $value;
        }
    }

    public function toArray(): array
    {
        $attributes = [];
        foreach ($this->getAttributes() as $key => $value) {
            $attributes[$key] = $this->getAttribute($key);
        }

        foreach ($this->relations as $key => $relation) {
            if (is_array($relation)) {
                $attributes[$key] = array_map(function ($item) {
                    return $item instanceof Model ? $item->toArray() : $item;
                }, $relation);
            } elseif ($relation instanceof Model) {
                $attributes[$key] = $relation->toArray();
            } else {
                $attributes[$key] = $relation;
            }
        }

        foreach ($this->appends as $key) {
            $attributes[$key] = $this->getAttribute($key);
        }

        // Apply visible filter
        if (!empty($this->visible)) {
            $attributes = array_intersect_key($attributes, array_flip($this->visible));
        }

        return array_diff_key($attributes, array_flip($this->hidden));
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function makeHidden($attributes): static
    {
        $this->hidden = array_merge($this->hidden, is_array($attributes) ? $attributes : func_get_args());
        return $this;
    }

    public function makeVisible($attributes): static
    {
        $this->hidden = array_diff($this->hidden, is_array($attributes) ? $attributes : func_get_args());
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }
    public function offsetExists($offset): bool
    {
        return $this->getAttribute($offset) !== null;
    }
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, 'scope' . ucfirst($method))) {
            return $this->{'scope' . ucfirst($method)}($this->newQuery(), ...$parameters);
        }
        return $this->newQuery()->$method(...$parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return static::query()->$method(...$parameters);
    }

    protected function generateKey()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    private function requireDatabase(): void
    {
        if (!Database::isEnabled())
            throw new Exception("Database is not enabled.");
    }
    protected function filterFillable(array $data): array
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }

        if ($this->guarded === ['*']) {
            return [];
        }

        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }

        return $data;
    }

    // ========================================================
    //  MODEL EVENTS
    // ========================================================

    protected function fireModelEvent(string $event): mixed
    {
        $handlers = static::$eventHandlers[static::class][$event] ?? [];
        foreach ($handlers as $handler) {
            $result = $handler($this);
            if ($result === false) {
                error_log("[MODEL EVENT] Event '{$event}' on model [" . static::class . "] returned false.");
                return false;
            }
        }
        return true;
    }

    public static function creating(callable $callback): void
    {
        static::registerEvent('creating', $callback);
    }
    public static function created(callable $callback): void
    {
        static::registerEvent('created', $callback);
    }
    public static function updating(callable $callback): void
    {
        static::registerEvent('updating', $callback);
    }
    public static function updated(callable $callback): void
    {
        static::registerEvent('updated', $callback);
    }
    public static function saving(callable $callback): void
    {
        static::registerEvent('saving', $callback);
    }
    public static function saved(callable $callback): void
    {
        static::registerEvent('saved', $callback);
    }
    public static function deleting(callable $callback): void
    {
        static::registerEvent('deleting', $callback);
    }
    public static function deleted(callable $callback): void
    {
        static::registerEvent('deleted', $callback);
    }
    public static function restoring(callable $callback): void
    {
        static::registerEvent('restoring', $callback);
    }
    public static function restored(callable $callback): void
    {
        static::registerEvent('restored', $callback);
    }
    public static function replicating(callable $callback): void
    {
        static::registerEvent('replicating', $callback);
    }

    protected static function registerEvent(string $event, callable $callback): void
    {
        static::$eventHandlers[static::class][$event][] = $callback;
    }

    /**
     * Run callback without firing model events
     */
    public static function withoutEvents(callable $callback): mixed
    {
        $events = static::$eventHandlers[static::class] ?? [];
        static::$eventHandlers[static::class] = [];
        try {
            return $callback();
        } finally {
            static::$eventHandlers[static::class] = $events;
        }
    }

    public static function flushEventListeners(): void
    {
        static::$eventHandlers[static::class] = [];
    }

    // ========================================================
    //  TOUCH OWNERS ($touches)
    // ========================================================

    protected function touchOwners(): void
    {
        foreach ($this->touches as $relation) {
            if (method_exists($this, $relation)) {
                $related = $this->$relation;
                if ($related instanceof Model) {
                    $related->touch();
                } elseif (is_array($related)) {
                    foreach ($related as $model) {
                        if ($model instanceof Model)
                            $model->touch();
                    }
                }
            }
        }
    }

    // ========================================================
    //  UTILITY METHODS
    // ========================================================

    public function is(?Model $model): bool
    {
        return $model !== null
            && $this->getKey() === $model->getKey()
            && $this->getTable() === $model->getTable();
    }

    public function isNot(?Model $model): bool
    {
        return !$this->is($model);
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getGuarded(): array
    {
        return $this->guarded;
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable))
            return true;
        if (in_array($key, $this->guarded) || $this->guarded === ['*'])
            return false;
        return empty($this->fillable);
    }

    public function isGuarded(string $key): bool
    {
        return !$this->isFillable($key);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    public function __debugInfo(): array
    {
        return [
            'table' => $this->getTable(),
            'primaryKey' => $this->primaryKey,
            'exists' => $this->exists,
            'attributes' => $this->attributes,
            'dirty' => $this->getDirty(),
            'relations' => array_keys($this->relations),
        ];
    }
}
