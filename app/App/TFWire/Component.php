<?php

namespace TheFramework\App\TFWire;

use TheFramework\App\Http\View;

/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  TFWire Component — The Turbo-Powered Livewire Alternative  ║
 * ║  Version: 1.0.0 | License: MIT                              ║
 * ║                                                              ║
 * ║  Features:                                                   ║
 * ║  • Stateful Components with Lifecycle Hooks                  ║
 * ║  • Two-Way Data Binding (tf-wire:model)                      ║
 * ║  • Server Method Calling (tf-wire:click)                     ║
 * ║  • Validation Engine (Laravel-compatible rules)              ║
 * ║  • Events System (emit, listen, browser dispatch)            ║
 * ║  • Computed Properties with Caching                          ║
 * ║  • Pagination Built-in                                       ║
 * ║  • Query String Binding                                      ║
 * ║  • Polling / Auto-Refresh                                    ║
 * ║  • Lazy Loading                                              ║
 * ║  • Authorization Hooks                                       ║
 * ║  • Locked Properties (readonly from frontend)                ║
 * ║  • Dirty State Tracking                                      ║
 * ║  • Flash Messages                                            ║
 * ║  • Redirect with Turbo                                       ║
 * ╚══════════════════════════════════════════════════════════════╝
 */
abstract class Component
{
    // ╔══════════════════════════════════════════════════════════╗
    // ║  IDENTITY                                                ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Unique component ID (used as Turbo Frame ID) */
    public string $id;

    /** Component name (auto-generated from class basename) */
    protected string $componentName;

    // ╔══════════════════════════════════════════════════════════╗
    // ║  STATE MANAGEMENT                                        ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Snapshot of initial state (for dirty tracking) */
    private array $originalState = [];

    /** Properties that CAN be set from frontend (whitelist) */
    protected array $fillable = [];

    /** Properties LOCKED from frontend modification */
    protected array $locked = [];

    /** Properties excluded from serialization to frontend */
    protected array $hidden = [
        'fillable', 'locked', 'hidden', 'rules', 'messages',
        'listeners', 'queryString', 'pollInterval', 'lazy',
        'authorize',
    ];

    // ╔══════════════════════════════════════════════════════════╗
    // ║  VALIDATION                                              ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Validation rules (Laravel-compatible format) */
    protected array $rules = [];

    /** Custom validation messages */
    protected array $messages = [];

    /** Current validation errors */
    protected array $errors = [];

    // ╔══════════════════════════════════════════════════════════╗
    // ║  EVENTS                                                  ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Event listeners: ['eventName' => 'methodName'] */
    protected array $listeners = [];

    /** Queued events to dispatch after render */
    private array $eventQueue = [];

    /** Queued browser dispatches (for Alpine.js) */
    private array $browserDispatchQueue = [];

    // ╔══════════════════════════════════════════════════════════╗
    // ║  PAGINATION                                              ║
    // ╚══════════════════════════════════════════════════════════╝

    public int $page = 1;
    public int $perPage = 15;

    // ╔══════════════════════════════════════════════════════════╗
    // ║  ADVANCED FEATURES                                       ║
    // ╚══════════════════════════════════════════════════════════╝

    /** Query string binding: ['search' => ['except' => '']] */
    protected array $queryString = [];

    /** Auto-refresh interval in milliseconds (0 = disabled) */
    protected int $pollInterval = 0;

    /** Lazy load component (render placeholder first) */
    protected bool $lazy = false;

    /** Authorization check before rendering */
    protected bool $authorize = false;

    // ╔══════════════════════════════════════════════════════════╗
    // ║  INTERNAL                                                ║
    // ╚══════════════════════════════════════════════════════════╝

    private bool $skipRender = false;
    private array $computedCache = [];
    private array $flashBag = [];
    private ?string $redirectTo = null;

    // ═══════════════════════════════════════════════════════════
    //  CONSTRUCTOR
    // ═══════════════════════════════════════════════════════════

    public function __construct(?string $id = null)
    {
        $basename = class_basename(static::class);
        $this->componentName = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $basename));
        $this->id = $id ?? 'tf-' . $this->componentName . '-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);

        $this->syncFromQueryString();
    }

    // ═══════════════════════════════════════════════════════════
    //  LIFECYCLE HOOKS (Override in child class as needed)
    // ═══════════════════════════════════════════════════════════

    /** Called once when component is first created */
    public function mount(): void {}

    /** Called every time state is restored from a request */
    public function hydrate(): void {}

    /** Called before state is serialized for next request */
    public function dehydrate(): void {}

    /** Called when any property changes */
    public function updated(string $property, $value): void {}

    /** Called before the view is rendered */
    public function rendering(): void {}

    /** Called after the view is rendered */
    public function rendered(string &$html): void {}

    /** Called before any action is executed */
    public function beforeAction(string $action): bool { return true; }

    /** Called after an action is executed */
    public function afterAction(string $action): void {}

    /** Authorization check — throw exception if unauthorized */
    public function authorizeAccess(): bool { return true; }

    // ═══════════════════════════════════════════════════════════
    //  STATE MANAGEMENT
    // ═══════════════════════════════════════════════════════════

    /**
     * Get all public properties as key-value array
     */
    public function getPublicProperties(): array
    {
        $reflect = new \ReflectionClass($this);
        $data = [];
        $skip = ['id'];

        foreach ($reflect->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
            $name = $prop->getName();
            if (in_array($name, $skip) || in_array($name, $this->hidden)) continue;
            if ($prop->getDeclaringClass()->getName() === self::class && in_array($name, ['page', 'perPage'])) {
                $data[$name] = $prop->getValue($this);
                continue;
            }
            if ($prop->isInitialized($this)) {
                $data[$name] = $prop->getValue($this);
            }
        }

        return $data;
    }

    /**
     * Fill component state from external data (respects fillable & locked)
     */
    public function fill(array $data): void
    {
        $allowed = !empty($this->fillable)
            ? $this->fillable
            : array_keys($this->getPublicProperties());

        // Remove locked properties from allowed list
        $allowed = array_diff($allowed, $this->locked);

        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed) || !property_exists($this, $key)) continue;

            $old = $this->{$key} ?? null;

            // Type casting based on existing property type
            $this->{$key} = $this->castValue($key, $value);

            // Generic updated hook
            $this->updated($key, $this->{$key});

            // Specific hook: updatedSearch($newValue, $oldValue)
            $camelKey = str_replace('_', '', ucwords($key, '_'));
            $method = 'updated' . $camelKey;
            if (method_exists($this, $method)) {
                $this->{$method}($this->{$key}, $old);
            }
        }
    }

    /**
     * Cast value to match existing property type
     */
    private function castValue(string $key, $value)
    {
        if (!property_exists($this, $key)) return $value;

        $reflect = new \ReflectionProperty($this, $key);
        $type = $reflect->getType();

        if (!$type || !($type instanceof \ReflectionNamedType)) return $value;

        return match ($type->getName()) {
            'int'    => (int) $value,
            'float'  => (float) $value,
            'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            'array'  => is_array($value) ? $value : json_decode($value, true) ?? [],
            default  => $value,
        };
    }

    /**
     * Check if any (or specific) property changed since mount/hydrate
     */
    public function isDirty(?string $property = null): bool
    {
        $current = $this->getPublicProperties();
        if ($property) {
            return ($this->originalState[$property] ?? null) !== ($current[$property] ?? null);
        }
        return $this->originalState !== $current;
    }

    /**
     * Get only the changed properties
     */
    public function getDirty(): array
    {
        $dirty = [];
        $current = $this->getPublicProperties();
        foreach ($current as $key => $value) {
            if (($this->originalState[$key] ?? null) !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Reset properties to their original values
     */
    public function reset(...$properties): void
    {
        $targets = empty($properties)
            ? array_keys($this->originalState)
            : $properties;

        foreach ($targets as $key) {
            if (isset($this->originalState[$key]) && property_exists($this, $key)) {
                $this->{$key} = $this->originalState[$key];
            }
        }
    }

    /**
     * Capture current state as "clean" (reset dirty tracking)
     */
    public function saveState(): void
    {
        $this->originalState = $this->getPublicProperties();
    }

    // ═══════════════════════════════════════════════════════════
    //  VALIDATION ENGINE
    // ═══════════════════════════════════════════════════════════

    /**
     * Validate component properties against defined rules
     * @throws \TheFramework\App\Exceptions\ValidationException
     */
    public function validate(?array $rules = null, ?array $messages = null): array
    {
        $rules    = $rules ?? $this->rules;
        $messages = $messages ?? $this->messages;
        $data     = $this->getPublicProperties();
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $fieldRules = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (empty($rule)) continue;
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->applyRule($field, $value, $rule, $params, $data);
                if ($error) {
                    $customKey = "{$field}.{$rule}";
                    $this->errors[$field][] = $messages[$customKey] ?? $messages[$field] ?? $error;
                    break;
                }
            }
        }

        if (!empty($this->errors)) {
            throw new \TheFramework\App\Exceptions\ValidationException($this->errors);
        }

        return array_intersect_key($data, $rules);
    }

    /**
     * Validate a single property in real-time
     */
    public function validateOnly(string $field, ?array $rules = null): void
    {
        $rules = $rules ?? $this->rules;
        if (!isset($rules[$field])) return;

        try {
            $this->validate([$field => $rules[$field]]);
        } catch (\TheFramework\App\Exceptions\ValidationException $e) {
            // Keep errors but don't throw — allows UI to show inline errors
        }
    }

    /**
     * Apply a single validation rule
     */
    protected function applyRule(string $field, $value, string $rule, array $params, array $allData): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'  => (empty($value) && $value !== '0' && $value !== 0 && $value !== false)
                            ? "{$label} wajib diisi." : null,
            'string'    => (!is_null($value) && !is_string($value))
                            ? "{$label} harus berupa teks." : null,
            'numeric'   => (!is_null($value) && $value !== '' && !is_numeric($value))
                            ? "{$label} harus berupa angka." : null,
            'integer'   => (!is_null($value) && $value !== '' && !ctype_digit((string)$value))
                            ? "{$label} harus berupa bilangan bulat." : null,
            'email'     => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                            ? "{$label} harus berupa email yang valid." : null,
            'url'       => ($value && !filter_var($value, FILTER_VALIDATE_URL))
                            ? "{$label} harus berupa URL yang valid." : null,
            'min'       => $this->checkMin($label, $value, $params[0] ?? 0),
            'max'       => $this->checkMax($label, $value, $params[0] ?? PHP_INT_MAX),
            'between'   => $this->checkBetween($label, $value, $params[0] ?? 0, $params[1] ?? PHP_INT_MAX),
            'in'        => (!in_array($value, $params))
                            ? "{$label} harus salah satu dari: " . implode(', ', $params) . "." : null,
            'not_in'    => (in_array($value, $params))
                            ? "{$label} tidak boleh berisi: " . implode(', ', $params) . "." : null,
            'confirmed' => ($value !== ($allData[$field . '_confirmation'] ?? null))
                            ? "Konfirmasi {$label} tidak cocok." : null,
            'regex'     => ($value && !preg_match($params[0] ?? '/.*/', $value))
                            ? "{$label} format tidak valid." : null,
            'nullable'  => null,
            'boolean'   => (!is_null($value) && !is_bool($value) && !in_array($value, [0, 1, '0', '1'], true))
                            ? "{$label} harus bernilai true atau false." : null,
            'array'     => (!is_null($value) && !is_array($value))
                            ? "{$label} harus berupa array." : null,
            'date'      => ($value && strtotime($value) === false)
                            ? "{$label} harus berupa tanggal yang valid." : null,
            default     => null,
        };
    }

    private function checkMin(string $label, $value, $min): ?string
    {
        $min = (int) $min;
        if (is_string($value) && strlen($value) < $min) return "{$label} minimal {$min} karakter.";
        if (is_numeric($value) && $value < $min) return "{$label} minimal bernilai {$min}.";
        if (is_array($value) && count($value) < $min) return "{$label} minimal {$min} item.";
        return null;
    }

    private function checkMax(string $label, $value, $max): ?string
    {
        $max = (int) $max;
        if (is_string($value) && strlen($value) > $max) return "{$label} maksimal {$max} karakter.";
        if (is_numeric($value) && $value > $max) return "{$label} maksimal bernilai {$max}.";
        if (is_array($value) && count($value) > $max) return "{$label} maksimal {$max} item.";
        return null;
    }

    private function checkBetween(string $label, $value, $min, $max): ?string
    {
        $min = (int) $min;
        $max = (int) $max;
        if (is_numeric($value) && ($value < $min || $value > $max)) {
            return "{$label} harus antara {$min} dan {$max}.";
        }
        if (is_string($value) && (strlen($value) < $min || strlen($value) > $max)) {
            return "{$label} harus antara {$min} dan {$max} karakter.";
        }
        return null;
    }

    /** Get current errors */
    public function getErrors(): array { return $this->errors; }

    /** Check if a field has error */
    public function hasError(string $field): bool { return isset($this->errors[$field]); }

    /** Get first error message for a field */
    public function getError(string $field): ?string { return $this->errors[$field][0] ?? null; }

    /** Add a manual error */
    public function addError(string $field, string $message): void { $this->errors[$field][] = $message; }

    /** Reset errors */
    public function resetErrors(?string $field = null): void
    {
        if ($field) {
            $this->errors[$field] = [];
        } else {
            $this->errors = [];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EVENTS SYSTEM
    // ═══════════════════════════════════════════════════════════

    /** Emit event to other components */
    public function emit(string $event, ...$params): void
    {
        $this->eventQueue[] = ['event' => $event, 'params' => $params, 'scope' => 'global'];
    }

    /** Emit event to self only */
    public function emitSelf(string $event, ...$params): void
    {
        if (isset($this->listeners[$event]) && method_exists($this, $this->listeners[$event])) {
            $this->{$this->listeners[$event]}(...$params);
        }
    }

    /** Emit event to parent component */
    public function emitUp(string $event, ...$params): void
    {
        $this->eventQueue[] = ['event' => $event, 'params' => $params, 'scope' => 'parent'];
    }

    /** Dispatch browser/Alpine.js event */
    public function dispatchBrowserEvent(string $event, array $data = []): void
    {
        $this->browserDispatchQueue[] = ['event' => $event, 'data' => $data];
    }

    /** Get queued events */
    public function getEventQueue(): array { return $this->eventQueue; }

    /** Get browser dispatch queue */
    public function getBrowserDispatchQueue(): array { return $this->browserDispatchQueue; }

    // ═══════════════════════════════════════════════════════════
    //  COMPUTED PROPERTIES (with caching)
    // ═══════════════════════════════════════════════════════════

    /**
     * Magic getter for computed properties.
     * Define: getFullNameProperty() → access via $this->fullName
     */
    public function __get(string $name)
    {
        $method = 'get' . str_replace('_', '', ucwords($name, '_')) . 'Property';

        if (method_exists($this, $method)) {
            // Cache computed result within same render cycle
            if (!isset($this->computedCache[$name])) {
                $this->computedCache[$name] = $this->{$method}();
            }
            return $this->computedCache[$name];
        }

        return null;
    }

    /** Clear computed cache (useful after state change) */
    protected function clearComputedCache(?string $property = null): void
    {
        if ($property) {
            unset($this->computedCache[$property]);
        } else {
            $this->computedCache = [];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  PAGINATION
    // ═══════════════════════════════════════════════════════════

    public function gotoPage(int $page): void { $this->page = max(1, $page); }
    public function nextPage(): void { $this->page++; }
    public function previousPage(): void { $this->page = max(1, $this->page - 1); }
    public function resetPage(): void { $this->page = 1; }
    protected function getOffset(): int { return ($this->page - 1) * $this->perPage; }

    /**
     * Generate pagination info array
     */
    protected function getPaginationInfo(int $total): array
    {
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        return [
            'current_page' => $this->page,
            'per_page'     => $this->perPage,
            'total'        => $total,
            'last_page'    => $lastPage,
            'from'         => ($this->page - 1) * $this->perPage + 1,
            'to'           => min($this->page * $this->perPage, $total),
            'has_previous' => $this->page > 1,
            'has_next'     => $this->page < $lastPage,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  FLASH MESSAGES
    // ═══════════════════════════════════════════════════════════

    /** Set flash message (available for next render only) */
    protected function flash(string $type, string $message): void
    {
        $this->flashBag[] = ['type' => $type, 'message' => $message];
    }

    protected function flashSuccess(string $message): void { $this->flash('success', $message); }
    protected function flashError(string $message): void { $this->flash('error', $message); }
    protected function flashWarning(string $message): void { $this->flash('warning', $message); }
    protected function flashInfo(string $message): void { $this->flash('info', $message); }

    // ═══════════════════════════════════════════════════════════
    //  QUERY STRING SYNC
    // ═══════════════════════════════════════════════════════════

    protected function syncFromQueryString(): void
    {
        foreach ($this->queryString as $prop => $options) {
            $key = is_string($options) ? $options : $prop;
            $propName = is_int($prop) ? $options : $prop;

            if (isset($_GET[$key]) && property_exists($this, $propName)) {
                $except = is_array($options) ? ($options['except'] ?? null) : null;
                $val = $_GET[$key];

                if ($except !== null && $val == $except) continue;
                $this->{$propName} = $this->castValue($propName, $val);
            }
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  RENDERING ENGINE
    // ═══════════════════════════════════════════════════════════

    /** Return Blade view name — MUST be overridden */
    abstract protected function view(): string;

    /**
     * Optional: Return placeholder HTML for lazy-loaded components
     */
    protected function placeholder(): string
    {
        return '<div class="tf-wire-placeholder" style="opacity:0.5;text-align:center;padding:2rem;">'
             . '<div class="tf-wire-spinner"></div>'
             . '<p style="margin-top:0.5rem;color:#9CA3AF;">Loading...</p>'
             . '</div>';
    }

    /**
     * Render component to HTML wrapped in Turbo Frame
     */
    public function render(): string
    {
        // Authorization check
        if ($this->authorize && !$this->authorizeAccess()) {
            return "<!-- TFWire: Unauthorized -->";
        }

        // Lazy loading: show placeholder first
        if ($this->lazy && !$this->isSubsequentRequest()) {
            return $this->renderLazy();
        }

        // Lifecycle
        $this->clearComputedCache();
        $this->rendering();

        // Collect data for view
        $data = array_merge($this->getPublicProperties(), [
            '_component' => $this,
            '_errors'    => $this->errors,
            '_id'        => $this->id,
            '_flash'     => $this->flashBag,
        ]);

        // Render Blade view
        $html = (string) View::render($this->view(), $data);

        // Lifecycle post-render
        $this->rendered($html);

        // Build Turbo Frame wrapper
        return $this->wrapInFrame($html);
    }

    /**
     * Render as lazy-loaded component (placeholder + turbo-frame[src])
     */
    protected function renderLazy(): string
    {
        $src = '/tfwire/handle?' . http_build_query([
            '_tf_class' => static::class,
            '_tf_id'    => $this->id,
            '_tf_lazy'  => 1,
        ]);

        return "<!-- TFWire Lazy: " . class_basename(static::class) . " -->\n"
             . "<turbo-frame id=\"{$this->id}\" src=\"{$src}\" loading=\"lazy\" data-controller=\"tfwire\">\n"
             . "  " . $this->placeholder() . "\n"
             . "</turbo-frame>";
    }

    /**
     * Wrap rendered HTML in a Turbo Frame with all necessary metadata
     */
    protected function wrapInFrame(string $html): string
    {
        $this->dehydrate();
        $state = $this->serializeState();

        // Build data attributes
        $attrs = 'data-controller="tfwire"';
        if ($this->pollInterval > 0) {
            $attrs .= " data-tf-poll=\"{$this->pollInterval}\"";
        }

        // Hidden fields for state management
        $hiddenFields = implode("\n  ", [
            '<input type="hidden" name="_tf_state" value="' . htmlspecialchars($state) . '">',
            '<input type="hidden" name="_tf_id" value="' . htmlspecialchars($this->id) . '">',
            '<input type="hidden" name="_tf_class" value="' . htmlspecialchars(static::class) . '">',
        ]);

        // Browser event dispatches as script tags
        $scripts = '';
        foreach ($this->browserDispatchQueue as $dispatch) {
            $eventData = htmlspecialchars(json_encode($dispatch['data']));
            $scripts .= "\n  <script>document.dispatchEvent(new CustomEvent('{$dispatch['event']}', {detail: {$eventData}}))</script>";
        }

        // Flash messages — render menggunakan internal notification.blade.php
        $toasts = '';
        foreach ($this->flashBag as $flash) {
            $notifData = ['notification' => ['status' => $flash['type'], 'message' => $flash['message']]];
            $toasts .= "\n  " . (string) View::render('notification.notification', $notifData);
        }

        return "<!-- TFWire: " . class_basename(static::class) . " [{$this->id}] -->\n"
             . "<turbo-frame id=\"{$this->id}\" {$attrs}>\n"
             . "  {$hiddenFields}\n"
             . $html
             . $toasts
             . $scripts
             . "\n</turbo-frame>";
    }

    /**
     * Render as Turbo Stream response (for partial updates)
     */
    public function renderAsStream(string $action = 'replace'): string
    {
        return (new TurboStream())->buildRaw($action, $this->id, $this->render());
    }

    // ═══════════════════════════════════════════════════════════
    //  STATE SERIALIZATION
    // ═══════════════════════════════════════════════════════════

    public function serializeState(): string
    {
        $data = $this->getPublicProperties();
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return base64_encode($json);
    }

    public function hydrateState(string $state): void
    {
        $data = json_decode(base64_decode($state), true);
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key) && !in_array($key, $this->locked)) {
                    $this->{$key} = $value;
                }
            }
        }
        $this->hydrate();
        $this->originalState = $this->getPublicProperties();
    }

    // ═══════════════════════════════════════════════════════════
    //  ACTION HANDLING
    // ═══════════════════════════════════════════════════════════

    /**
     * Call a public method on this component (from frontend request)
     * @throws \BadMethodCallException
     */
    public function callAction(string $method, array $params = []): mixed
    {
        // Security: block internal methods
        $blocked = [
            'render', 'view', 'mount', 'hydrate', 'dehydrate',
            'serializeState', 'hydrateState', 'fill', 'wrapInFrame',
            'validate', 'callAction', 'getPublicProperties', 'placeholder',
        ];

        if (in_array($method, $blocked)) {
            throw new \BadMethodCallException("Method [{$method}] is protected and cannot be called from frontend.");
        }

        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method [{$method}] not found on component [" . static::class . "].");
        }

        // Lifecycle: before action
        if (!$this->beforeAction($method)) {
            return null;
        }

        $this->clearComputedCache();
        $result = $this->{$method}(...$params);

        // Lifecycle: after action
        $this->afterAction($method);

        return $result;
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════

    /** Create a TurboStream builder */
    protected function stream(): TurboStream { return new TurboStream(); }

    /** Skip rendering (use with redirect) */
    protected function skipRender(): void { $this->skipRender = true; }
    public function shouldSkipRender(): bool { return $this->skipRender; }

    /** Redirect after action (Turbo-compatible) */
    protected function redirect(string $url): void
    {
        $this->redirectTo = $url;
        $this->skipRender();
    }

    public function getRedirectUrl(): ?string { return $this->redirectTo; }

    /** Check if this is a subsequent (non-initial) request */
    protected function isSubsequentRequest(): bool
    {
        return isset($_POST['_tf_state']) || isset($_GET['_tf_lazy']);
    }

    /** Get the component name */
    public function getName(): string { return $this->componentName; }
}
