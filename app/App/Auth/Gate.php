<?php

namespace TheFramework\App\Auth;

use TheFramework\App\Exceptions\AuthorizationException;

/**
 * Gate — Authorization System (Enterprise-Grade)
 * 
 * Gate menyediakan cara untuk mendefinisikan dan memeriksa izin (abilities).
 * Ini adalah INTI dari sistem RBAC.
 * 
 * Mendukung:
 * - Define abilities (closure-based)
 * - Policy classes (resource-based authorization)
 * - Before/After hooks (super admin bypass, logging)
 * - Ability aliases & groups
 * - Response objects (allow/deny with messages)
 * 
 * @example
 * // Define abilities
 * Gate::define('edit-post', function($user, $post) {
 *     return $user->id === $post->user_id;
 * });
 * 
 * // Define using Policy
 * Gate::policy(Post::class, PostPolicy::class);
 * 
 * // Check authorization
 * Gate::allows('edit-post', $post);
 * Gate::denies('edit-post', $post);
 * Gate::authorize('edit-post', $post); // throws AuthorizationException
 * 
 * // Super admin bypass
 * Gate::before(function($user, $ability) {
 *     if ($user->isSuperAdmin()) return true;
 * });
 * 
 * @package TheFramework\App\Auth
 * @version 5.1.0
 */
class Gate
{
    /**
     * Registered abilities [name => callable]
     */
    protected static array $abilities = [];

    /**
     * Registered policies [Model::class => PolicyClass::class]
     */
    protected static array $policies = [];

    /**
     * Before callbacks — jalankan SEBELUM ability check
     * Jika return true/false, skip ability check
     */
    protected static array $beforeCallbacks = [];

    /**
     * After callbacks — jalankan SETELAH ability check
     * Untuk logging, auditing
     */
    protected static array $afterCallbacks = [];

    /**
     * Ability aliases/groups
     * 'manage-posts' => ['create-post', 'edit-post', 'delete-post']
     */
    protected static array $abilityGroups = [];

    /**
     * The user resolver callback
     */
    public static ?\Closure $userResolver = null;

    /**
     * Cached policy instances [class => instance]
     */
    protected static array $policyInstances = [];

    // ========================================================
    //  USER RESOLVER
    // ========================================================

    /**
     * Set the user resolver
     * Gate butuh tahu siapa user yang sedang login
     * 
     * @example
     * Gate::resolveUsing(function() {
     *     return auth()->user();
     * });
     */
    public static function resolveUsing(\Closure $resolver): void
    {
        static::$userResolver = $resolver;
    }

    /**
     * Resolve the current user
     */
    protected static function resolveUser(): mixed
    {
        if (static::$userResolver) {
            return call_user_func(static::$userResolver);
        }

        // Default: coba dari AuthManager
        return AuthManager::user();
    }

    // ========================================================
    //  DEFINE ABILITIES
    // ========================================================

    /**
     * Define a new ability
     * 
     * @param string $ability Nama ability (misal: 'update-post', 'users.edit')
     * @param callable $callback fn($user, ...$args): bool|GateResponse
     */
    public static function define(string $ability, callable $callback): void
    {
        static::$abilities[$ability] = $callback;
    }

    /**
     * Define multiple abilities at once
     * 
     * @example Gate::defineMany([
     *     'create-user' => fn($user) => $user->hasPermission('create-user'),
     *     'edit-user'   => fn($user, $target) => $user->id === $target->id,
     * ]);
     */
    public static function defineMany(array $abilities): void
    {
        foreach ($abilities as $ability => $callback) {
            static::define($ability, $callback);
        }
    }

    /**
     * Register a policy class for a model
     * 
     * @param string $model Model class name (TheFramework\Models\Post)
     * @param string $policy Policy class name (App\Policies\PostPolicy)
     */
    public static function policy(string $model, string $policy): void
    {
        static::$policies[$model] = $policy;
    }

    /**
     * Register multiple policies
     */
    public static function policies(array $policies): void
    {
        foreach ($policies as $model => $policy) {
            static::$policies[$model] = $policy;
        }
    }

    /**
     * Register a before callback (runs before any ability check)
     * 
     * Return true = always allow (super admin)
     * Return false = always deny
     * Return null = continue to ability check
     * 
     * @example
     * Gate::before(function($user, $ability) {
     *     if ($user->hasRole('superadmin')) return true;
     * });
     */
    public static function before(callable $callback): void
    {
        static::$beforeCallbacks[] = $callback;
    }

    /**
     * Register an after callback (runs after ability check)
     * For audit logging, analytics, etc.
     * 
     * @example
     * Gate::after(function($user, $ability, $result, $args) {
     *     AuditLog::log($user, $ability, $result);
     * });
     */
    public static function after(callable $callback): void
    {
        static::$afterCallbacks[] = $callback;
    }

    /**
     * Define an ability group (alias for multiple abilities)
     * 
     * @example
     * Gate::group('manage-posts', ['create-post', 'edit-post', 'delete-post']);
     * Gate::allows('manage-posts', $post); // checks if user can do ANY of these
     */
    public static function group(string $name, array $abilities): void
    {
        static::$abilityGroups[$name] = $abilities;
    }

    // ========================================================
    //  CHECK ABILITIES
    // ========================================================

    /**
     * Determine if the given ability should be granted for the current user
     * 
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    public static function allows(string $ability, mixed ...$arguments): bool
    {
        $response = static::inspect($ability, ...$arguments);
        return $response->allowed();
    }

    /**
     * Determine if the given ability should be denied for the current user
     */
    public static function denies(string $ability, mixed ...$arguments): bool
    {
        return !static::allows($ability, ...$arguments);
    }

    /**
     * Check ability and throw AuthorizationException if denied
     * 
     * @throws AuthorizationException
     */
    public static function authorize(string $ability, mixed ...$arguments): GateResponse
    {
        $response = static::inspect($ability, ...$arguments);

        if ($response->denied()) {
            throw new AuthorizationException($response->message() ?: 'This action is unauthorized.');
        }

        return $response;
    }

    /**
     * Check if the user has ANY of the given abilities
     */
    public static function any(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if (static::allows($ability, ...$arguments)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user has ALL of the given abilities
     */
    public static function all(array $abilities, mixed ...$arguments): bool
    {
        foreach ($abilities as $ability) {
            if (static::denies($ability, ...$arguments)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the user has NONE of the given abilities (all denied)
     */
    public static function none(array $abilities, mixed ...$arguments): bool
    {
        return !static::any($abilities, ...$arguments);
    }

    /**
     * Inspect an ability and return a detailed response
     */
    public static function inspect(string $ability, mixed ...$arguments): GateResponse
    {
        $user = static::resolveUser();

        // If no user is authenticated, deny by default
        if (is_null($user)) {
            return GateResponse::deny('Unauthenticated.');
        }

        // Run before callbacks
        $beforeResult = static::callBeforeCallbacks($user, $ability, $arguments);
        if (!is_null($beforeResult)) {
            $response = $beforeResult
                ? GateResponse::allow()
                : GateResponse::deny('Authorization denied by before hook.');

            static::callAfterCallbacks($user, $ability, $response, $arguments);
            return $response;
        }

        // Check ability groups
        if (isset(static::$abilityGroups[$ability])) {
            foreach (static::$abilityGroups[$ability] as $groupedAbility) {
                if (static::rawCheck($user, $groupedAbility, $arguments)) {
                    $response = GateResponse::allow();
                    static::callAfterCallbacks($user, $ability, $response, $arguments);
                    return $response;
                }
            }
            $response = GateResponse::deny("None of the abilities in group '{$ability}' are allowed.");
            static::callAfterCallbacks($user, $ability, $response, $arguments);
            return $response;
        }

        // Check direct ability or policy
        $result = static::rawCheck($user, $ability, $arguments);
        $response = $result instanceof GateResponse ? $result : ($result ? GateResponse::allow() : GateResponse::deny());

        // Run after callbacks
        static::callAfterCallbacks($user, $ability, $response, $arguments);

        return $response;
    }

    /**
     * Raw ability check (no before/after hooks)
     */
    protected static function rawCheck(mixed $user, string $ability, array $arguments): bool|GateResponse
    {
        // 1. Check direct ability definition
        if (isset(static::$abilities[$ability])) {
            return call_user_func(static::$abilities[$ability], $user, ...$arguments);
        }

        // 2. Check policy for the first argument (if it's an object)
        if (!empty($arguments)) {
            $model = $arguments[0];
            $modelClass = is_object($model) ? get_class($model) : (is_string($model) ? $model : null);

            if ($modelClass) {
                $policy = static::resolvePolicy($modelClass);
                if ($policy) {
                    // Convert ability name to method (e.g., 'edit-post' -> 'edit', 'posts.update' -> 'update')
                    $method = static::abilityToMethod($ability);

                    if (method_exists($policy, 'before')) {
                        $beforeResult = $policy->before($user, $ability);
                        if (!is_null($beforeResult)) {
                            return $beforeResult;
                        }
                    }

                    if (method_exists($policy, $method)) {
                        return call_user_func([$policy, $method], $user, ...$arguments);
                    }
                }
            }
        }

        // 3. Check if user model has can() method (trait-based RBAC)
        if (is_object($user) && method_exists($user, 'hasPermission')) {
            return $user->hasPermission($ability);
        }

        // Not defined = deny
        return false;
    }

    // ========================================================
    //  POLICY RESOLUTION
    // ========================================================

    /**
     * Resolve a policy instance for a model class
     */
    protected static function resolvePolicy(string $modelClass): ?object
    {
        // Check direct registration
        $policyClass = static::$policies[$modelClass] ?? null;

        // Auto-discover: Model\User -> Policies\UserPolicy
        if (!$policyClass) {
            $policyClass = static::guessPolicyClass($modelClass);
        }

        if (!$policyClass || !class_exists($policyClass)) {
            return null;
        }

        // Cache the instance
        if (!isset(static::$policyInstances[$policyClass])) {
            static::$policyInstances[$policyClass] = new $policyClass();
        }

        return static::$policyInstances[$policyClass];
    }

    /**
     * Auto-guess policy class name from model class
     * TheFramework\Models\User -> TheFramework\Policies\UserPolicy
     * App\Models\Post -> App\Policies\PostPolicy
     */
    protected static function guessPolicyClass(string $modelClass): ?string
    {
        $shortName = class_basename($modelClass);
        $guesses = [
            "TheFramework\\Policies\\{$shortName}Policy",
            "App\\Policies\\{$shortName}Policy",
        ];

        foreach ($guesses as $guess) {
            if (class_exists($guess)) {
                return $guess;
            }
        }

        return null;
    }

    /**
     * Convert ability name to policy method name
     * 'update-post' -> 'update'
     * 'posts.create' -> 'create'
     * 'viewAny' -> 'viewAny'
     */
    protected static function abilityToMethod(string $ability): string
    {
        // Remove resource prefix: 'posts.update' -> 'update'
        if (str_contains($ability, '.')) {
            $ability = substr($ability, strrpos($ability, '.') + 1);
        }

        // Remove model suffix: 'update-post' -> 'update'
        if (str_contains($ability, '-')) {
            $parts = explode('-', $ability);
            return $parts[0];
        }

        return $ability;
    }

    // ========================================================
    //  BEFORE/AFTER CALLBACKS
    // ========================================================

    protected static function callBeforeCallbacks(mixed $user, string $ability, array $arguments): ?bool
    {
        foreach (static::$beforeCallbacks as $callback) {
            $result = $callback($user, $ability, $arguments);
            if (!is_null($result)) {
                return (bool) $result;
            }
        }
        return null;
    }

    protected static function callAfterCallbacks(mixed $user, string $ability, GateResponse $response, array $arguments): void
    {
        foreach (static::$afterCallbacks as $callback) {
            $callback($user, $ability, $response, $arguments);
        }
    }

    // ========================================================
    //  ADMINISTRATION
    // ========================================================

    /**
     * Check if an ability has been defined
     */
    public static function has(string $ability): bool
    {
        return isset(static::$abilities[$ability])
            || isset(static::$abilityGroups[$ability]);
    }

    /**
     * Get all defined abilities
     */
    public static function abilities(): array
    {
        return array_keys(static::$abilities);
    }

    /**
     * Get all registered policies
     */
    public static function getPolicies(): array
    {
        return static::$policies;
    }

    /**
     * Get policy for a specific model
     */
    public static function getPolicyFor(string $modelClass): ?string
    {
        return static::$policies[$modelClass] ?? null;
    }

    /**
     * Get or set the policy for a model
     */
    public static function forUser(mixed $user): GateForUser
    {
        return new GateForUser($user);
    }

    /**
     * Reset all gate definitions (useful for testing)
     */
    public static function flush(): void
    {
        static::$abilities = [];
        static::$policies = [];
        static::$beforeCallbacks = [];
        static::$afterCallbacks = [];
        static::$abilityGroups = [];
        static::$policyInstances = [];
    }
}


/**
 * GateResponse — Authorization Check Result
 * 
 * Memberikan detail mengapa akses diizinkan atau ditolak.
 */
class GateResponse
{
    protected bool $allowed;
    protected ?string $message;
    protected ?string $code;

    public function __construct(bool $allowed, ?string $message = null, ?string $code = null)
    {
        $this->allowed = $allowed;
        $this->message = $message;
        $this->code = $code;
    }

    public static function allow(?string $message = null, ?string $code = null): static
    {
        return new static(true, $message, $code);
    }

    public static function deny(?string $message = null, ?string $code = null): static
    {
        return new static(false, $message ?? 'This action is unauthorized.', $code);
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function denied(): bool
    {
        return !$this->allowed;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    /**
     * Throw exception if denied
     */
    public function authorize(): static
    {
        if ($this->denied()) {
            throw new AuthorizationException($this->message ?? 'This action is unauthorized.');
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->message ?? '';
    }
}


/**
 * GateForUser — Check abilities as a specific user
 * 
 * @example Gate::forUser($admin)->allows('delete-user', $target)
 */
class GateForUser
{
    protected mixed $user;

    public function __construct(mixed $user)
    {
        $this->user = $user;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        // Temporarily override the user resolver
        $originalResolver = Gate::$userResolver;
        $user = $this->user;
        Gate::resolveUsing(fn() => $user);

        $result = Gate::allows($ability, ...$arguments);

        // Restore original resolver
        Gate::$userResolver = $originalResolver;

        return $result;
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): GateResponse
    {
        $originalResolver = Gate::$userResolver;
        $user = $this->user;
        Gate::resolveUsing(fn() => $user);

        $result = Gate::authorize($ability, ...$arguments);

        Gate::$userResolver = $originalResolver;

        return $result;
    }
}


/**
 * Helper function for getting class basename
 */
if (!function_exists('class_basename')) {
    function class_basename(string $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
