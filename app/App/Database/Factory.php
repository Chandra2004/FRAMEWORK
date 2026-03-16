<?php

namespace TheFramework\App\Database;

use TheFramework\Helpers\Collection;
use Exception;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  Factory — Model Data Factory Engine                        ║
 * ║  Version: 5.1.0 | License: MIT                             ║
 * ║                                                             ║
 * ║  Generate dummy data untuk testing & seeding.               ║
 * ║  Terinspirasi dari Laravel Eloquent Factory.                ║
 * ║                                                             ║
 * ║  Features:                                                  ║
 * ║  • Fluent API (chainable)                                   ║
 * ║  • Faker Integration                                        ║
 * ║  • States (variasi data)                                    ║
 * ║  • Sequences (data berurutan)                               ║
 * ║  • Relationships (afterCreating hooks)                      ║
 * ║  • make() vs create() (tanpa/dengan save ke DB)             ║
 * ║  • Count support (bulk generation)                          ║
 * ║  • Override attributes                                      ║
 * ║  • Recycle (reuse existing model instances)                  ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
abstract class Factory
{
    /**
     * Faker instance (shared across factories)
     * @var object|null \Faker\Generator
     */
    protected static ?object $fakerInstance = null;

    /**
     * Jumlah model yang akan dibuat
     */
    protected int $count = 1;

    /**
     * State closures yang akan di-apply
     */
    protected array $states = [];

    /**
     * After-creating callbacks
     */
    protected array $afterCreating = [];

    /**
     * After-making callbacks
     */
    protected array $afterMaking = [];

    /**
     * Sequence data untuk iterasi
     */
    protected ?array $sequence = null;

    /**
     * Sequence index tracker
     */
    protected int $sequenceIndex = 0;

    /**
     * Override attributes (dari user)
     */
    protected array $overrides = [];

    /**
     * Model instances yang di-recycle (reuse)
     */
    protected array $recycle = [];

    // ═══════════════════════════════════════════════════════════
    //  ABSTRACT — Harus diimplementasi oleh child factory
    // ═══════════════════════════════════════════════════════════

    /**
     * Definisi atribut default model.
     * WAJIB di-override oleh setiap Factory.
     *
     * @return array
     */
    abstract public function definition(): array;

    /**
     * Nama model class (FQCN) yang dihasilkan factory ini.
     * WAJIB di-override oleh setiap Factory.
     *
     * Contoh: return \TheFramework\Models\User::class;
     *
     * @return string
     */
    abstract public function model(): string;

    // ═══════════════════════════════════════════════════════════
    //  FAKER
    // ═══════════════════════════════════════════════════════════

    /**
     * Dapatkan Faker instance.
     * Locale default: 'id_ID' (Indonesia).
     *
     * @return object \Faker\Generator
     */
    protected function faker(): object
    {
        if (static::$fakerInstance === null) {
            if (!class_exists('Faker\Factory')) {
                throw new Exception(
                    "Library Faker belum terinstall!\n" .
                    "Jalankan: composer require fakerphp/faker --dev"
                );
            }
            $locale = \TheFramework\App\Core\Config::get('app.faker_locale', 'id_ID');
            $fakerFactory = 'Faker\Factory';
            static::$fakerInstance = $fakerFactory::create($locale);
        }
        return static::$fakerInstance;
    }

    /**
     * Shortcut agar bisa langsung `$this->fake()->name()` di definition()
     *
     * @return object \Faker\Generator
     */
    protected function fake(): object
    {
        return $this->faker();
    }

    // ═══════════════════════════════════════════════════════════
    //  STATIC CONSTRUCTORS
    // ═══════════════════════════════════════════════════════════

    /**
     * Buat instance factory baru.
     * 
     * @param int $count Jumlah data yang ingin dibuat
     * @return static
     */
    public static function new(int $count = 1): static
    {
        $factory = new static();
        $factory->count = $count;
        return $factory;
    }

    /**
     * Shortcut: Factory::times(10)->create()
     */
    public static function times(int $count): static
    {
        return static::new($count);
    }

    // ═══════════════════════════════════════════════════════════
    //  FLUENT CONFIGURATION
    // ═══════════════════════════════════════════════════════════

    /**
     * Set jumlah model yang akan dibuat.
     */
    public function count(int $count): static
    {
        $clone = clone $this;
        $clone->count = $count;
        return $clone;
    }

    /**
     * Apply state ke factory.
     * State adalah closure yang memodifikasi atribut.
     *
     * @param string|callable $state Nama method state atau closure
     * @return static
     */
    public function state(string|callable $state): static
    {
        $clone = clone $this;

        if (is_string($state) && method_exists($this, $state)) {
            $clone->states[] = $this->{$state}();
        } elseif (is_callable($state)) {
            $clone->states[] = $state;
        }

        return $clone;
    }

    /**
     * Set data berurutan (sequence) untuk setiap iterasi.
     *
     * Contoh:
     *   ->sequence(['role' => 'admin'], ['role' => 'user'], ['role' => 'editor'])
     *   
     *   Menghasilkan: admin, user, editor, admin, user, editor, ...
     *
     * @param array ...$sequence
     * @return static
     */
    public function sequence(array ...$sequence): static
    {
        $clone = clone $this;
        $clone->sequence = $sequence;
        $clone->sequenceIndex = 0;
        return $clone;
    }

    /**
     * Register callback yang dijalankan SETELAH model di-create (saved ke DB).
     */
    public function afterCreating(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterCreating[] = $callback;
        return $clone;
    }

    /**
     * Register callback yang dijalankan SETELAH model di-make (tanpa save).
     */
    public function afterMaking(callable $callback): static
    {
        $clone = clone $this;
        $clone->afterMaking[] = $callback;
        return $clone;
    }

    /**
     * Recycle (reuse) model instance yang sudah ada.
     * Berguna untuk relasi agar tidak membuat parent baru setiap kali.
     *
     * Contoh:
     *   $user = User::factory()->create();
     *   Post::factory()->count(5)->recycle($user)->create();
     *
     * @param Model|array $models
     * @return static
     */
    public function recycle(Model|array $models): static
    {
        $clone = clone $this;
        $models = is_array($models) ? $models : [$models];

        foreach ($models as $model) {
            $class = get_class($model);
            $clone->recycle[$class] = $model;
        }

        return $clone;
    }

    /**
     * Dapatkan recycled model berdasarkan class.
     * Return null jika tidak ada.
     */
    protected function getRecycled(string $modelClass): ?Model
    {
        return $this->recycle[$modelClass] ?? null;
    }

    // ═══════════════════════════════════════════════════════════
    //  CORE: MAKE & CREATE
    // ═══════════════════════════════════════════════════════════

    /**
     * Buat model instance TANPA menyimpan ke database.
     * Cocok untuk unit testing tanpa dependency DB.
     *
     * @param array $attributes Override attributes
     * @return Model|Collection
     */
    public function make(array $attributes = []): Model|Collection
    {
        $this->overrides = $attributes;
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $attrs = $this->resolveAttributes($i);
            $model = $this->newModelInstance($attrs);
            $model->exists = false;

            // Run afterMaking callbacks
            foreach ($this->afterMaking as $callback) {
                $callback($model);
            }

            $results[] = $model;
        }

        // Reset sequence
        $this->sequenceIndex = 0;

        if ($this->count === 1) {
            return $results[0];
        }

        return new Collection($results);
    }

    /**
     * Buat model instance DAN simpan ke database.
     * Ini adalah method utama yang paling sering digunakan.
     *
     * @param array $attributes Override attributes
     * @return Model|Collection
     */
    public function create(array $attributes = []): Model|Collection
    {
        $this->overrides = $attributes;
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $attrs = $this->resolveAttributes($i);
            $modelClass = $this->model();
            $model = $modelClass::create($attrs);

            // Run afterCreating callbacks
            foreach ($this->afterCreating as $callback) {
                $callback($model);
            }

            $results[] = $model;
        }

        // Reset sequence
        $this->sequenceIndex = 0;

        if ($this->count === 1) {
            return $results[0];
        }

        return new Collection($results);
    }

    /**
     * Buat raw array attributes TANPA model instance.
     * Berguna jika butuh array mentah saja.
     *
     * @param array $attributes
     * @return array
     */
    public function raw(array $attributes = []): array
    {
        $this->overrides = $attributes;
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $results[] = $this->resolveAttributes($i);
        }

        $this->sequenceIndex = 0;

        return $this->count === 1 ? $results[0] : $results;
    }

    // ═══════════════════════════════════════════════════════════
    //  INTERNAL: ATTRIBUTE RESOLUTION
    // ═══════════════════════════════════════════════════════════

    /**
     * Resolve final attributes untuk satu model.
     * Urutan prioritas: definition() → states → sequence → overrides
     */
    protected function resolveAttributes(int $index): array
    {
        // 1. Base definition
        $attributes = $this->definition();

        // 2. Resolve callable values (lazy evaluation)
        foreach ($attributes as $key => $value) {
            if ($value instanceof \Closure) {
                $attributes[$key] = $value($attributes);
            }
        }

        // 3. Apply states
        foreach ($this->states as $state) {
            if (is_callable($state)) {
                $stateAttrs = $state($attributes);
                if (is_array($stateAttrs)) {
                    $attributes = array_merge($attributes, $stateAttrs);
                }
            }
        }

        // 4. Apply sequence (round-robin)
        if ($this->sequence !== null && !empty($this->sequence)) {
            $seqIdx = $this->sequenceIndex % count($this->sequence);
            $seqData = $this->sequence[$seqIdx];

            // Resolve callable values dalam sequence
            foreach ($seqData as $key => $value) {
                if ($value instanceof \Closure) {
                    $seqData[$key] = $value($attributes);
                }
            }

            $attributes = array_merge($attributes, $seqData);
            $this->sequenceIndex++;
        }

        // 5. Apply user overrides (highest priority)
        $attributes = array_merge($attributes, $this->overrides);

        return $attributes;
    }

    /**
     * Buat instance model baru dan fill attributes.
     */
    protected function newModelInstance(array $attributes): Model
    {
        $modelClass = $this->model();

        if (!class_exists($modelClass)) {
            throw new Exception("Model class [{$modelClass}] tidak ditemukan.");
        }

        $model = new $modelClass();
        
        // Set attributes langsung
        foreach ($attributes as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }

    // ═══════════════════════════════════════════════════════════
    //  CONVENIENCE STATES (override di child class)
    // ═══════════════════════════════════════════════════════════

    /**
     * Contoh predefined state.
     * Override di child factory:
     *
     *   public function admin(): callable {
     *       return fn() => ['role' => 'admin'];
     *   }
     *
     *   // Usage: User::factory()->state('admin')->create();
     */

    // ═══════════════════════════════════════════════════════════
    //  RELATIONSHIP HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Buat model beserta relasi has-many.
     *
     * Contoh:
     *   User::factory()->has(Post::factory()->count(3), 'posts')->create();
     *
     * @param Factory $factory  Factory untuk relasi
     * @param string  $relation Nama method relasi di model parent
     * @return static
     */
    public function has(Factory $factory, string $relation): static
    {
        return $this->afterCreating(function (Model $model) use ($factory, $relation) {
            // Determine foreign key dari model name
            $parentClass = get_class($model);
            $baseName = class_basename($parentClass);
            $foreignKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName)) . '_id';
            $primaryKey = $model->{$model->getKeyName() ?? 'id'};

            // Create related models dengan foreign key
            $factory->create([$foreignKey => $primaryKey]);
        });
    }

    /**
     * Buat model dengan relasi belongs-to (parent).
     *
     * Contoh:
     *   Post::factory()->for(User::factory(), 'user')->create();
     *   Post::factory()->for($existingUser, 'user')->create();
     *
     * @param Factory|Model $parent Factory atau model instance yang sudah ada
     * @param string $relation      Nama relasi
     * @return static
     */
    public function for(Factory|Model $parent, string $relation): static
    {
        return $this->state(function () use ($parent, $relation) {
            if ($parent instanceof Factory) {
                $parentModel = $parent->create();
            } else {
                $parentModel = $parent;
            }

            $baseName = class_basename(get_class($parentModel));
            $foreignKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $baseName)) . '_id';
            $primaryKey = $parentModel->{$parentModel->getKeyName() ?? 'id'};

            return [$foreignKey => $primaryKey];
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  UTILITY
    // ═══════════════════════════════════════════════════════════

    /**
     * Get the model key name.
     */
    protected function getModelKeyName(): string
    {
        $modelClass = $this->model();
        $instance = new $modelClass();
        return $instance->getKeyName() ?? 'id';
    }

    /**
     * Reset Faker instance (berguna saat testing).
     */
    public static function resetFaker(): void
    {
        static::$fakerInstance = null;
    }

    /**
     * Set custom Faker locale.
     */
    public static function setFakerLocale(string $locale): void
    {
        if (!class_exists('Faker\Factory')) {
            throw new Exception(
                "Library Faker belum terinstall!\n" .
                "Jalankan: composer require fakerphp/faker --dev"
            );
        }
        $fakerFactory = 'Faker\Factory';
        static::$fakerInstance = $fakerFactory::create($locale);
    }
}
