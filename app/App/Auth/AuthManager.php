<?php

namespace TheFramework\App\Auth;

use TheFramework\App\Core\Config;

/**
 * AuthManager — Authentication Facade (Enterprise-Grade)
 * 
 * Menyediakan interface tunggal untuk:
 * - Login / Logout
 * - Cek autentikasi (check, guest)
 * - Akses user saat ini (user, id)
 * - Session-based dan token-based auth
 * - Remember me
 * - Login throttling integration
 * 
 * @example
 * auth()->login($user);
 * auth()->check();
 * auth()->user();
 * auth()->logout();
 * auth()->id();
 * 
 * @package TheFramework\App\Auth
 * @version 5.1.0
 */
class AuthManager
{
    /**
     * The cached user instance
     */
    protected static mixed $cachedUser = null;

    /**
     * Whether we've attempted to resolve the user this request
     */
    protected static bool $userResolved = false;

    /**
     * The user model class to use
     */
    protected static ?string $userModel = null;

    /**
     * The session key for storing authenticated user identifier
     */
    protected static string $sessionKey = 'auth_user_id';

    /**
     * Custom user resolver (optional override)
     */
    protected static ?\Closure $userResolver = null;

    /**
     * Login event callbacks
     */
    protected static array $loginCallbacks = [];

    /**
     * Logout event callbacks
     */
    protected static array $logoutCallbacks = [];

    /**
     * Authenticated event callbacks
     */
    protected static array $authenticatedCallbacks = [];

    // ========================================================
    //  CONFIGURATION
    // ========================================================

    /**
     * Configure the auth manager
     */
    public static function configure(?string $userModel = null, ?string $sessionKey = null): void
    {
        if ($userModel) {
            static::$userModel = $userModel;
        }

        if ($sessionKey) {
            static::$sessionKey = $sessionKey;
        }
    }

    /**
     * Set the user model class
     */
    public static function useModel(string $modelClass): void
    {
        static::$userModel = $modelClass;
    }

    /**
     * Get the user model class
     */
    public static function getUserModel(): string
    {
        if (static::$userModel) {
            return static::$userModel;
        }

        // Auto-detect from config
        $model = Config::get('auth.model', Config::get('AUTH_MODEL', 'TheFramework\\Models\\System\\User'));

        static::$userModel = $model;
        return $model;
    }

    /**
     * Override user resolver
     */
    public static function resolveUsing(\Closure $callback): void
    {
        static::$userResolver = $callback;
    }

    // ========================================================
    //  AUTHENTICATION STATE
    // ========================================================

    /**
     * Get the currently authenticated user
     * 
     * @return mixed|null The user model instance or null
     */
    public static function user(): mixed
    {
        if (static::$userResolved) {
            return static::$cachedUser;
        }

        static::$userResolved = true;

        // Custom resolver takes priority
        if (static::$userResolver) {
            static::$cachedUser = call_user_func(static::$userResolver);
            return static::$cachedUser;
        }

        // Session-based authentication
        static::ensureSession();

        $userId = $_SESSION[static::$sessionKey] ?? null;

        if (!$userId) {
            static::$cachedUser = null;
            return null;
        }

        // Resolve user from model
        $modelClass = static::getUserModel();

        if (!class_exists($modelClass)) {
            static::$cachedUser = null;
            return null;
        }

        try {
            static::$cachedUser = $modelClass::find($userId);
        } catch (\Throwable $e) {
            static::$cachedUser = null;
        }

        return static::$cachedUser;
    }

    /**
     * Get the ID of the currently authenticated user
     */
    public static function id(): mixed
    {
        static::ensureSession();
        return $_SESSION[static::$sessionKey] ?? null;
    }

    /**
     * Check if a user is authenticated
     */
    public static function check(): bool
    {
        return static::user() !== null;
    }

    /**
     * Check if the current user is a guest (not authenticated)
     */
    public static function guest(): bool
    {
        return !static::check();
    }

    /**
     * Get the current user, or abort with 401 if not authenticated
     */
    public static function userOrFail(): mixed
    {
        $user = static::user();

        if (!$user) {
            throw new \TheFramework\App\Exceptions\AuthenticationException(
                'Unauthenticated.',
                [],
                static::getRedirectUrl()
            );
        }

        return $user;
    }

    // ========================================================
    //  LOGIN / LOGOUT
    // ========================================================

    /**
     * Login a user (by user model instance)
     * 
     * @param mixed $user The user model instance
     * @param bool $remember Whether to set remember-me cookie
     */
    public static function login(mixed $user, bool $remember = false): void
    {
        static::ensureSession();

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Get user ID
        $userId = static::extractUserId($user);

        // Store in session
        $_SESSION[static::$sessionKey] = $userId;
        $_SESSION['auth_logged_in_at'] = time();

        // Cache the user
        static::$cachedUser = $user;
        static::$userResolved = true;

        // Remember me
        if ($remember) {
            static::setRememberToken($user);
        }

        // Fire login callbacks
        foreach (static::$loginCallbacks as $callback) {
            $callback($user, $remember);
        }
    }

    /**
     * Login a user by their ID
     */
    public static function loginById(mixed $id, bool $remember = false): mixed
    {
        $modelClass = static::getUserModel();

        if (!class_exists($modelClass)) {
            return null;
        }

        $user = $modelClass::find($id);

        if ($user) {
            static::login($user, $remember);
        }

        return $user;
    }

    /**
     * Attempt to log in with credentials
     * 
     * @param array $credentials ['email' => '...', 'password' => '...']
     * @param bool $remember
     * @return bool True if authentication was successful
     */
    public static function attempt(array $credentials, bool $remember = false): bool
    {
        $modelClass = static::getUserModel();

        if (!class_exists($modelClass)) {
            return false;
        }

        // Extract password from credentials
        $password = $credentials['password'] ?? null;
        unset($credentials['password']);

        if (!$password) {
            return false;
        }

        // Find user by remaining credentials
        $query = $modelClass::query();

        foreach ($credentials as $key => $value) {
            $query->where($key, '=', $value);
        }

        $user = $query->first();

        if (!$user) {
            return false;
        }

        // Verify password
        $passwordField = static::getPasswordField($user);

        if (!$passwordField || !password_verify($password, $user->{$passwordField})) {
            return false;
        }

        static::login($user, $remember);
        return true;
    }

    /**
     * Validate credentials tanpa login
     */
    public static function validate(array $credentials): bool
    {
        $modelClass = static::getUserModel();

        if (!class_exists($modelClass)) {
            return false;
        }

        $password = $credentials['password'] ?? null;
        unset($credentials['password']);

        if (!$password) {
            return false;
        }

        $query = $modelClass::query();

        foreach ($credentials as $key => $value) {
            $query->where($key, '=', $value);
        }

        $user = $query->first();

        if (!$user) {
            return false;
        }

        $passwordField = static::getPasswordField($user);

        return $passwordField && password_verify($password, $user->{$passwordField});
    }

    /**
     * Logout the current user
     */
    public static function logout(): void
    {
        $user = static::user();

        static::ensureSession();

        // Clear remember me token
        if ($user && method_exists($user, 'setRememberToken')) {
            $user->setRememberToken(null);
            try {
                $user->save();
            } catch (\Throwable) {
            }
        }

        // Clear session data
        unset($_SESSION[static::$sessionKey]);
        unset($_SESSION['auth_logged_in_at']);

        // Regenerate session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Clear cache
        static::$cachedUser = null;
        static::$userResolved = false;

        // Remove remember cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }

        // Fire logout callbacks
        foreach (static::$logoutCallbacks as $callback) {
            $callback($user);
        }
    }

    // ========================================================
    //  REMEMBER ME
    // ========================================================

    /**
     * Set remember-me token for a user
     */
    protected static function setRememberToken(mixed $user): void
    {
        $token = bin2hex(random_bytes(32));

        // Store token on user model if supported
        if (method_exists($user, 'setRememberToken')) {
            $user->setRememberToken($token);
            try {
                $user->save();
            } catch (\Throwable) {
            }
        }

        // Set cookie (30 days)
        $expiry = time() + (30 * 24 * 60 * 60);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        setcookie('remember_token', $token, [
            'expires'  => $expiry,
            'path'     => '/',
            'secure'   => $secure,
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Attempt to authenticate via remember-me cookie
     */
    public static function viaRemember(): bool
    {
        if (static::check()) {
            return true;
        }

        $token = $_COOKIE['remember_token'] ?? null;

        if (!$token) {
            return false;
        }

        $modelClass = static::getUserModel();

        if (!class_exists($modelClass) || !method_exists($modelClass, 'findByRememberToken')) {
            return false;
        }

        $user = $modelClass::findByRememberToken($token);

        if ($user) {
            static::login($user);
            return true;
        }

        return false;
    }

    // ========================================================
    //  EVENT HOOKS
    // ========================================================

    /**
     * Register a login event callback
     * 
     * @example AuthManager::onLogin(function($user, $remember) {
     *     ActivityLog::log('login', $user);
     * });
     */
    public static function onLogin(callable $callback): void
    {
        static::$loginCallbacks[] = $callback;
    }

    /**
     * Register a logout event callback
     */
    public static function onLogout(callable $callback): void
    {
        static::$logoutCallbacks[] = $callback;
    }

    /**
     * Register a callback that runs when user is authenticated
     */
    public static function onAuthenticated(callable $callback): void
    {
        static::$authenticatedCallbacks[] = $callback;
    }

    // ========================================================
    //  HELPERS
    // ========================================================

    /**
     * Ensure session is started
     */
    protected static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
            session_start();
        }
    }

    /**
     * Extract user ID from user instance
     */
    protected static function extractUserId(mixed $user): mixed
    {
        if (method_exists($user, 'getKey')) {
            return $user->getKey();
        }

        if (method_exists($user, 'getAuthIdentifier')) {
            return $user->getAuthIdentifier();
        }

        return $user->id ?? $user->uid ?? null;
    }

    /**
     * Get password field name from user model
     */
    protected static function getPasswordField(mixed $user): ?string
    {
        if (method_exists($user, 'getAuthPasswordName')) {
            return $user->getAuthPasswordName();
        }

        // Auto-detect common password field names
        $candidates = ['password', 'password_hash', 'pass_hash'];

        foreach ($candidates as $field) {
            if (isset($user->$field) || (method_exists($user, 'getAttribute') && $user->getAttribute($field) !== null)) {
                return $field;
            }
        }

        return 'password';
    }

    /**
     * Get redirect URL for unauthenticated users
     */
    protected static function getRedirectUrl(): string
    {
        return Config::get('auth.login_url', Config::get('AUTH_LOGIN_URL', '/login'));
    }

    /**
     * Reset state (for testing)
     */
    public static function flush(): void
    {
        static::$cachedUser = null;
        static::$userResolved = false;
        static::$loginCallbacks = [];
        static::$logoutCallbacks = [];
        static::$authenticatedCallbacks = [];
        static::$userResolver = null;
    }

    /**
     * Set the cached user manually (for testing or manual override)
     */
    public static function setUser(mixed $user): void
    {
        static::$cachedUser = $user;
        static::$userResolved = true;
    }
}
