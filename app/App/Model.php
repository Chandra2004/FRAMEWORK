<?php
namespace TheFramework\App;

use ReflectionClass;
use Exception;

/**
 * @method static \TheFramework\App\QueryBuilder query()
 * @method static array all()
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
abstract class Model implements \JsonSerializable
{
    // ... code ...

    /**
     * Alias untuk insert (Gaya Laravel)
     */
    protected function create(array $data)
    {
        return $this->insert($data);
    }
    protected $table;
    protected $primaryKey = 'id';
    protected $db;
    protected $builder;

    protected $with = [];
    protected $fillable = [];
    protected $hidden = [];

    // ... (kode construct dan lain-lain)

    /**
     * Filter data based on fillable property.
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            // Jika fillable kosong, anggap semua data boleh (unsafe mode)
            // Atau bisa diubah policy-nya menjadi 'block all' jika mau strict.
            // Untuk mirip Laravel, jika fillable didefinisikan, maka strict.
            // Jika guarded tidak ada (kita pakai logic sederhana fillable saja).
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

        $id = $this->query()->insert($filteredData);

        // Return data lengkap setelah insert (mirip Laravel)
        // Gabungkan data input dengan ID baru
        if ($id && is_string($this->primaryKey)) {
            $filteredData[$this->primaryKey] = $id;
        }

        return $filteredData;
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
    public function toArray()
    {
        // Karena framework ini belum fully object-mapped (return query masih array/stdClass),
        // logic ini bekerja jika User memanggil Model sebagai object data (Active Record full).
        // Tapi jika resultnya masih array mentah dari database, logic hidden ini
        // harus diterapkan manual atau via Response Helper.

        // Asumsi: Property object adalah data kolom
        $data = get_object_vars($this);

        // Remove hidden attributes
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }

        // Remove internal protected properties (db, builder, etc)
        $protected = ['db', 'builder', 'table', 'primaryKey', 'fillable', 'hidden', 'with'];
        $data = array_diff_key($data, array_flip($protected));

        return $data;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

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

    protected function where($column, $value)
    {
        $this->requireDatabase();
        $results = $this->query()
            ->where($column, '=', $value)
            ->get();

        return $this->loadRelations($results, $this->with);
    }





    protected function delete($id)
    {
        $this->requireDatabase();
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
        // 1. Cek Method/Relationship (e.g. $user->posts)
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        // 2. Cek Properti Class (Protected/Public properties)
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        // 3. Cek Dynamic Attribute (Hasil Query Database)
        // PDO FETCH_OBJ mengembalikan stdClass/Object, propertinya bersifat publik dinamis.
        // Namun karena kita ada di dalam class, kita bisa akses $this->name jika itu ada.

        // Trik: Cek apakah properti ini ada di object instance secara runtime
        // Logika ini menangani kasus FETCH_CLASS atau jika Model di-hydrate manual
        if (isset($this->$name)) {
            return $this->$name;
        }

        // Jika tidak ketemu, return NULL alih-alih Error (Safe Null Object Pattern)
        // Atau biarkan default PHP behavior (Notice: Undefined property)
        // Tapi framework modern biasanya return null.
        return null;
    }

    /**
     * Handle method calls dynamically.
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        throw new Exception("Method '$method' tidak ditemukan di " . get_class($this));
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