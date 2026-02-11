<?php
namespace TheFramework\App;

use ReflectionClass;
use Exception;

use ArrayAccess;

/**
 * @method static \TheFramework\App\QueryBuilder query()
 * @method static array|mixed all()
 * @method static mixed find($id)
 * @method static mixed where($column, $value)
 * @method static mixed insert(array $data)
 * @method static mixed create(array $data)
 * @method static mixed update(array $data, $id)
 * @method static mixed delete($id)
 * @method static mixed findOrFail($id)
 * @method static mixed paginate(int $perPage = 10, int $page = 1)
 * @method static static with(array $relations)
 */
abstract class Model implements \JsonSerializable, ArrayAccess
{


    protected $table;
    protected $primaryKey = 'id';
    public $exists = false; // Menandakan apakah record ada di database
    protected $db;
    protected $builder;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    protected $with = [];
    protected $fillable = [];
    protected $hidden = [];

    /**
     * Static wrapper untuk create (Laravel style)
     * Usage: User::create(['name' => 'John', 'email' => 'john@example.com'])
     * @return static|null
     */
    public static function create(array $data)
    {
        $instance = new static;
        $insertedData = $instance->insert($data);

        if ($insertedData) {
            // Hydrate model dengan data yang baru diinsert
            foreach ($insertedData as $key => $value) {
                $instance->$key = $value;
            }
            return $instance;
        }

        return null;
    }

    /**
     * Instance method untuk insert (jika dipanggil via object)
     */
    public function createRecord(array $data)
    {
        return $this->insert($data);
    }

    /**
     * Get primary key value
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get primary key column name
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Fill the model with an array of attributes.
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->fillable === [] && $this->guarded === ['*']; // Logic sederhana

        foreach ($this->filterFillable($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Save the model to the database.
     */
    public function save(array $options = [])
    {
        // Jika model sudah ada (exists), lakukan UPDATE
        if ($this->exists) {
            $dirty = $this->getDirty(); // (Perlu implementasi getDirty, atau update semua)

            // Untuk saat ini update semua attributes kecuali ID dan timestamps (managed)
            if (empty($dirty) && count($this->attributes) > 0) {
                // Update semua jika logic getDirty belum ada
                $data = $this->attributes;
            } else {
                $data = $dirty;
            }

            // Hapus Primary Key dari data update agar tidak diupdate
            unset($data[$this->getKeyName()]);

            return $this->update($data, $this->getKey());
        }

        // Jika belum ada, lakukan INSERT
        $saved = $this->insert($this->attributes);
        if ($saved) {
            $this->exists = true;
            return true;
        }
        return false;
    }

    /**
     * Get attributes that have been changed since last sync.
     * (Placeholder implementation)
     */
    public function getDirty()
    {
        return $this->attributes; // Sementara return semua, bisa diimprove dgn $original
    }

    // ... (kode construct dan lain-lain)

    /**
     * Filter data based on fillable property.
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }


    /**
     * Aktifkan timestamps otomatis (created_at & updated_at)
     */
    protected $timestamps = true;

    // ... (property lain)

    /**
     * Handle timestamps automatically.
     */
    protected function manageTimestamps(array &$data, $type = 'create')
    {
        if (!$this->timestamps)
            return;

        $now = date('Y-m-d H:i:s');

        // Gunakan Helper waktu jika ada (opsional)
        if (class_exists('\\TheFramework\\Helpers\\Helper')) {
            $now = \TheFramework\Helpers\Helper::updateAt();
        }

        if ($type === 'create') {
            if (!isset($data['created_at'])) {
                $data['created_at'] = $now;
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = $now;
            }
        } else {
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = $now;
            }
        }
    }

    protected function insert(array $data)
    {
        $this->requireDatabase();
        $filteredData = $this->filterFillable($data);

        // Auto Timestamps
        $this->manageTimestamps($filteredData, 'create');

        $success = $this->query()->insert($filteredData);

        if ($success) {
            $this->exists = true; // Tandai bahwa record sudah tersimpan

            // Ambil ID terakhir yang diinsert
            try {
                $lastId = $this->db->lastInsertId();
                if ($lastId && is_string($this->primaryKey)) {
                    $filteredData[$this->primaryKey] = $lastId;
                    $this->setAttribute($this->primaryKey, $lastId); // Update attribute ID
                }
            } catch (\Exception $e) {
                // Ignore jika tabel tidak punya auto increment id
            }
            return $filteredData;
        }

        return false;
    }

    protected function update(array $data, $id)
    {
        $this->requireDatabase();
        $filteredData = $this->filterFillable($data);

        // Auto Timestamps
        $this->manageTimestamps($filteredData, 'update');

        $this->query()
            ->where($this->primaryKey, '=', $id)
            ->update($filteredData);

        return true;
    }

    protected function findOrFail($id)
    {
        $result = $this->find($id);
        if (!$result) {
            // Throw 404 Exception atau handle global error
            // Asumsi ada ErrorController atau Global Handler yg menangkap Exception
            throw new Exception("Data dengan ID $id tidak ditemukan", 404);
        }
        return $result;
    }

    // ... (method delete, paginate, dll)

    /**
     * Convert model to array for JSON serialization
     */


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

    protected function query(): QueryBuilder
    {
        return (new QueryBuilder($this->db))
            ->table($this->table)
            ->setModel($this);
    }

    protected function all()
    {
        $this->requireDatabase();
        $results = $this->query()->with($this->with)->get();
        return $this->loadRelations($results, $this->with);
    }

    protected function find($id)
    {
        $this->requireDatabase();
        $result = $this->query()
            ->where($this->primaryKey, '=', $id)
            ->first();

        if (!$result)
            return null;

        return $this->loadRelations([$result], $this->with)[0];
    }







    public function delete($id = null)
    {
        $this->requireDatabase();

        // Jika argumen ID kosong, gunakan ID instance ini
        if ($id === null) {
            $id = $this->getKey();
            if (!$id) {
                throw new Exception("Tidak bisa delete model tanpa Primary Key.");
            }
        }

        return $this->query()
            ->where($this->primaryKey, '=', $id)
            ->delete();
    }

    protected function paginate(int $perPage = 10, int $page = 1)
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

    protected function with(array $relations)
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
                $relatedPrototype = $relationObj->related;
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
            return get_class($relationObj->related);
        }
        throw new Exception("Tidak bisa menentukan class model untuk relasi '$relation'");
    }

    /* ==================================================
       🔹 HYDRATION & ATTRIBUTE ACCESS
    ================================================== */

    /**
     * Convert raw array results to Model instances.
     * 
     * @param array $results
     * @return array
     */
    public function hydrate(array $results)
    {
        $models = [];
        foreach ($results as $result) {
            $model = new static;
            $model->setRawAttributes((array) $result, true);
            $model->exists = true; // Tandai hasil query sebagai exists
            $models[] = $model;
        }
        return $models;
    }

    /**
     * Set the array of model attributes.
     *
     * @param  array  $attributes
     * @param  bool  $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        // Check method/relation
        if (method_exists($this, $key)) {
            $value = $this->$key();
            // Jika return value adalah Relation, lakukan Lazy Loading
            if ($value instanceof Relation) {
                // Simpan hasil lazy load ke attributes agar query tidak berulang (Caching)
                $results = $value->getResults();
                $this->setAttribute($key, $results);
                return $results;
            }
            return $value;
        }

        return null;
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /* ==================================================
       🔹 ARRAY ACCESS IMPLEMENTATION
    ================================================== */

    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
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
        unset($this->attributes[$offset]);
    }

    /**
     * Convert model to array for JSON serialization
     */
    public function toArray()
    {
        $data = $this->attributes; // Start with attributes

        // Add relations if loaded (bisa ditambahkan nanti logic ini)
        // Saat ini relations di-merge ke attributes secara manual oleh loadRelations?
        // Sebenarnya idealnya relations disimpan di property terpisah $relations.
        // Tapi untuk kompatibilitas framework simpel ini, kita asumsi relation masuk ke attributes sementara.

        // Remove hidden attributes
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }

        return $data;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Handle method calls dynamically.
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        // Forward call to QueryBuilder
        return $this->query()->$method(...$parameters);
    }

    /**
     * Handle static method calls dynamically.
     * Membuat bisa dipanggil via User::all(), User::find(1), dll.
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}