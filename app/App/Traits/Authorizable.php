<?php

namespace TheFramework\App\Traits;

use TheFramework\App\Auth\Gate;
use TheFramework\App\Auth\GateResponse;

/**
 * Authorizable — Trait for User Model (Enterprise-Grade)
 * 
 * Menambahkan kemampuan authorization langsung pada model User.
 * Ini memungkinkan pengecekan izin yang intuitif dan ekspresif.
 * 
 * @example
 * $user->can('edit-post', $post);
 * $user->cannot('delete-user', $target);
 * $user->hasRole('admin');
 * $user->hasAnyRole(['admin', 'editor']);
 * $user->hasPermission('users.create');
 * $user->assignRole('editor');
 * $user->removeRole('editor');
 * $user->givePermissionTo('posts.edit');
 * $user->revokePermissionTo('posts.edit');
 * 
 * @package TheFramework\App\Traits
 * @version 5.1.0
 */
trait Authorizable
{
    // ========================================================
    //  GATE-BASED AUTHORIZATION
    // ========================================================

    /**
     * Determine if the user has a given ability (Gate-based)
     * 
     * @param string $ability
     * @param mixed ...$arguments
     * @return bool
     */
    public function can(string $ability, mixed ...$arguments): bool
    {
        return Gate::forUser($this)->allows($ability, ...$arguments);
    }

    /**
     * Determine if the user does NOT have a given ability
     */
    public function cannot(string $ability, mixed ...$arguments): bool
    {
        return !$this->can($ability, ...$arguments);
    }

    /**
     * Alias for cannot()
     */
    public function cant(string $ability, mixed ...$arguments): bool
    {
        return $this->cannot($ability, ...$arguments);
    }

    /**
     * Authorize an ability or throw AuthorizationException
     */
    public function authorize(string $ability, mixed ...$arguments): GateResponse
    {
        return Gate::forUser($this)->authorize($ability, ...$arguments);
    }

    // ========================================================
    //  ROLE-BASED AUTHORIZATION
    // ========================================================

    /**
     * Check if user has a specific role
     * 
     * Menggunakan relasi 'roles' pada model User.
     * Bisa berupa string match pada kolom 'name' atau 'slug'.
     */
    public function hasRole(string ...$roles): bool
    {
        $userRoles = $this->getUserRoleNames();

        foreach ($roles as $role) {
            if (in_array(strtolower($role), $userRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ANY of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole(...$roles);
    }

    /**
     * Check if user has ALL of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->getUserRoleNames();

        foreach ($roles as $role) {
            if (!in_array(strtolower($role), $userRoles, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user does NOT have a specific role
     */
    public function doesntHaveRole(string $role): bool
    {
        return !$this->hasRole($role);
    }

    // ========================================================
    //  PERMISSION-BASED AUTHORIZATION
    // ========================================================

    /**
     * Check if user has a specific permission
     * 
     * Checks:
     * 1. Direct permissions assigned to user
     * 2. Permissions inherited from roles
     */
    public function hasPermission(string $permission): bool
    {
        // 1. Check direct permissions
        $directPermissions = $this->getUserPermissionNames();
        if (in_array(strtolower($permission), $directPermissions, true)) {
            return true;
        }

        // 2. Check role-based permissions
        $rolePermissions = $this->getRolePermissionNames();
        if (in_array(strtolower($permission), $rolePermissions, true)) {
            return true;
        }

        // 3. Check wildcard permissions (e.g., 'users.*' matches 'users.create')
        $allPermissions = array_merge($directPermissions, $rolePermissions);
        foreach ($allPermissions as $perm) {
            if ($this->matchesWildcard($perm, strtolower($permission))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ANY of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has ALL of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    // ========================================================
    //  ROLE MANAGEMENT
    // ========================================================

    /**
     * Assign a role to the user
     * 
     * @param string|int|object $role Role name, ID, or model instance
     */
    public function assignRole(string|int|object ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel && !$this->hasRole($roleModel->name ?? $roleModel->slug ?? '')) {
                $this->attachRole($roleModel);
            }
        }

        // Clear cached roles
        $this->clearRoleCache();

        return $this;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(string|int|object ...$roles): static
    {
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);

            if ($roleModel) {
                $this->detachRole($roleModel);
            }
        }

        $this->clearRoleCache();

        return $this;
    }

    /**
     * Sync roles — replace all roles with the given ones
     */
    public function syncRoles(array $roles): static
    {
        // Remove all current roles
        $this->detachAllRoles();

        // Assign new roles
        foreach ($roles as $role) {
            $roleModel = $this->resolveRole($role);
            if ($roleModel) {
                $this->attachRole($roleModel);
            }
        }

        $this->clearRoleCache();

        return $this;
    }

    // ========================================================
    //  PERMISSION MANAGEMENT
    // ========================================================

    /**
     * Give a direct permission to the user
     */
    public function givePermissionTo(string|int|object ...$permissions): static
    {
        foreach ($permissions as $permission) {
            $permModel = $this->resolvePermission($permission);

            if ($permModel) {
                $this->attachPermission($permModel);
            }
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Revoke a direct permission from the user
     */
    public function revokePermissionTo(string|int|object ...$permissions): static
    {
        foreach ($permissions as $permission) {
            $permModel = $this->resolvePermission($permission);

            if ($permModel) {
                $this->detachPermission($permModel);
            }
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Sync direct permissions
     */
    public function syncPermissions(array $permissions): static
    {
        $this->detachAllPermissions();

        foreach ($permissions as $permission) {
            $permModel = $this->resolvePermission($permission);
            if ($permModel) {
                $this->attachPermission($permModel);
            }
        }

        $this->clearPermissionCache();

        return $this;
    }

    // ========================================================
    //  DATA RETRIEVAL
    // ========================================================

    /**
     * Get all role names (lowercased) for quicker checking
     */
    protected function getUserRoleNames(): array
    {
        static $cached = [];
        $key = spl_object_id($this);

        if (isset($cached[$key])) {
            return $cached[$key];
        }

        $names = [];

        // Get via relationship if available
        if (method_exists($this, 'roles')) {
            $roles = $this->roles;

            if (is_array($roles)) {
                foreach ($roles as $role) {
                    $name = is_object($role)
                        ? ($role->slug ?? $role->name ?? '')
                        : ($role['slug'] ?? $role['name'] ?? '');
                    $names[] = strtolower($name);
                }
            }
        }

        $cached[$key] = $names;
        return $names;
    }

    /**
     * Get direct permission names (lowercased)
     */
    protected function getUserPermissionNames(): array
    {
        static $cached = [];
        $key = spl_object_id($this);

        if (isset($cached[$key])) {
            return $cached[$key];
        }

        $names = [];

        if (method_exists($this, 'permissions')) {
            $permissions = $this->permissions;

            if (is_array($permissions)) {
                foreach ($permissions as $perm) {
                    $name = is_object($perm)
                        ? ($perm->slug ?? $perm->name ?? '')
                        : ($perm['slug'] ?? $perm['name'] ?? '');
                    $names[] = strtolower($name);
                }
            }
        }

        $cached[$key] = $names;
        return $names;
    }

    /**
     * Get all permission names inherited from roles
     */
    protected function getRolePermissionNames(): array
    {
        static $cached = [];
        $key = spl_object_id($this);

        if (isset($cached[$key])) {
            return $cached[$key];
        }

        $names = [];

        if (method_exists($this, 'roles')) {
            $roles = $this->roles;

            if (is_array($roles)) {
                foreach ($roles as $role) {
                    if (is_object($role) && method_exists($role, '__get')) {
                        $rolePerms = $role->permissions ?? [];
                    } elseif (is_array($role) && isset($role['permissions'])) {
                        $rolePerms = $role['permissions'];
                    } else {
                        continue;
                    }

                    if (is_array($rolePerms)) {
                        foreach ($rolePerms as $perm) {
                            $name = is_object($perm)
                                ? ($perm->slug ?? $perm->name ?? '')
                                : ($perm['slug'] ?? $perm['name'] ?? '');
                            $names[] = strtolower($name);
                        }
                    }
                }
            }
        }

        $cached[$key] = array_unique($names);
        return $cached[$key];
    }

    /**
     * Get all permission names (direct + role-inherited)
     */
    public function getAllPermissions(): array
    {
        return array_unique(array_merge(
            $this->getUserPermissionNames(),
            $this->getRolePermissionNames()
        ));
    }

    /**
     * Get all role names
     */
    public function getRoleNames(): array
    {
        return $this->getUserRoleNames();
    }

    // ========================================================
    //  WILDCARD MATCHING
    // ========================================================

    /**
     * Check if a permission pattern matches (supports wildcards)
     * 
     * 'users.*' matches 'users.create', 'users.edit', etc.
     * '*' matches everything
     */
    protected function matchesWildcard(string $pattern, string $permission): bool
    {
        if ($pattern === '*') {
            return true;
        }

        if (!str_contains($pattern, '*')) {
            return $pattern === $permission;
        }

        $regex = str_replace('.', '\\.', $pattern);
        $regex = str_replace('*', '.*', $regex);

        return (bool) preg_match("/^{$regex}$/", $permission);
    }

    // ========================================================
    //  PIVOT OPERATIONS (Override in User model if needed)
    // ========================================================

    /**
     * Attach a role to the user (override in model for DB implementation)
     */
    protected function attachRole(object $role): void
    {
        // Default implementation using pivot table
        if (method_exists($this, 'getKey') && method_exists($role, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->rolesPivotTable ?? 'user_roles';
            $userKey = $this->rolesPivotUserKey ?? 'user_id';
            $roleKey = $this->rolesPivotRoleKey ?? 'role_id';

            $db->query("INSERT IGNORE INTO `{$table}` (`{$userKey}`, `{$roleKey}`) VALUES (:uid, :rid)");
            $db->bind(':uid', $this->getKey());
            $db->bind(':rid', $role->getKey());
            $db->execute();
        }
    }

    /**
     * Detach a role from the user
     */
    protected function detachRole(object $role): void
    {
        if (method_exists($this, 'getKey') && method_exists($role, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->rolesPivotTable ?? 'user_roles';
            $userKey = $this->rolesPivotUserKey ?? 'user_id';
            $roleKey = $this->rolesPivotRoleKey ?? 'role_id';

            $db->query("DELETE FROM `{$table}` WHERE `{$userKey}` = :uid AND `{$roleKey}` = :rid");
            $db->bind(':uid', $this->getKey());
            $db->bind(':rid', $role->getKey());
            $db->execute();
        }
    }

    /**
     * Detach all roles from the user
     */
    protected function detachAllRoles(): void
    {
        if (method_exists($this, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->rolesPivotTable ?? 'user_roles';
            $userKey = $this->rolesPivotUserKey ?? 'user_id';

            $db->query("DELETE FROM `{$table}` WHERE `{$userKey}` = :uid");
            $db->bind(':uid', $this->getKey());
            $db->execute();
        }
    }

    /**
     * Attach a permission directly to user
     */
    protected function attachPermission(object $permission): void
    {
        if (method_exists($this, 'getKey') && method_exists($permission, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->permissionsPivotTable ?? 'user_permissions';
            $userKey = $this->permissionsPivotUserKey ?? 'user_id';
            $permKey = $this->permissionsPivotPermKey ?? 'permission_id';

            $db->query("INSERT IGNORE INTO `{$table}` (`{$userKey}`, `{$permKey}`) VALUES (:uid, :pid)");
            $db->bind(':uid', $this->getKey());
            $db->bind(':pid', $permission->getKey());
            $db->execute();
        }
    }

    /**
     * Detach a permission from user
     */
    protected function detachPermission(object $permission): void
    {
        if (method_exists($this, 'getKey') && method_exists($permission, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->permissionsPivotTable ?? 'user_permissions';
            $userKey = $this->permissionsPivotUserKey ?? 'user_id';
            $permKey = $this->permissionsPivotPermKey ?? 'permission_id';

            $db->query("DELETE FROM `{$table}` WHERE `{$userKey}` = :uid AND `{$permKey}` = :pid");
            $db->bind(':uid', $this->getKey());
            $db->bind(':pid', $permission->getKey());
            $db->execute();
        }
    }

    /**
     * Detach all direct permissions from user
     */
    protected function detachAllPermissions(): void
    {
        if (method_exists($this, 'getKey')) {
            $db = \TheFramework\App\Database\Database::getInstance();
            $table = $this->permissionsPivotTable ?? 'user_permissions';
            $userKey = $this->permissionsPivotUserKey ?? 'user_id';

            $db->query("DELETE FROM `{$table}` WHERE `{$userKey}` = :uid");
            $db->bind(':uid', $this->getKey());
            $db->execute();
        }
    }

    // ========================================================
    //  RESOLVERS
    // ========================================================

    /**
     * Resolve role from string/int/object
     */
    protected function resolveRole(string|int|object $role): ?object
    {
        if (is_object($role)) {
            return $role;
        }

        $roleModelClass = $this->roleModelClass ?? 'TheFramework\\Models\\System\\Role';

        if (!class_exists($roleModelClass)) {
            return null;
        }

        if (is_int($role)) {
            return $roleModelClass::find($role);
        }

        // String — try by slug first, then by name
        return $roleModelClass::where('slug', '=', $role)->first()
            ?? $roleModelClass::where('name', '=', $role)->first();
    }

    /**
     * Resolve permission from string/int/object
     */
    protected function resolvePermission(string|int|object $permission): ?object
    {
        if (is_object($permission)) {
            return $permission;
        }

        $permModelClass = $this->permissionModelClass ?? 'TheFramework\\Models\\System\\Permission';

        if (!class_exists($permModelClass)) {
            return null;
        }

        if (is_int($permission)) {
            return $permModelClass::find($permission);
        }

        return $permModelClass::where('slug', '=', $permission)->first()
            ?? $permModelClass::where('name', '=', $permission)->first();
    }

    // ========================================================
    //  CACHE MANAGEMENT
    // ========================================================

    protected function clearRoleCache(): void
    {
        // Reset static cache by clearing the spl_object_id entry
        // This forces re-query on next access
        if (isset($this->relations['roles'])) {
            unset($this->relations['roles']);
        }
    }

    protected function clearPermissionCache(): void
    {
        if (isset($this->relations['permissions'])) {
            unset($this->relations['permissions']);
        }
    }
}
