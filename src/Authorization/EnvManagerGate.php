<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;

class EnvManagerGate
{
    public const PERMISSION_VIEW_ENV      = 'view-env';
    public const PERMISSION_EDIT_ENV      = 'edit-env';
    public const PERMISSION_DELETE_ENV    = 'delete-env';
    public const PERMISSION_BACKUP_ENV    = 'backup-env';
    public const PERMISSION_RESTORE_ENV   = 'restore-env';
    public const PERMISSION_REVEAL_SECRETS = 'reveal-secrets';

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_READ_ONLY   = 'read_only';

    private static array $rolePermissions = [
        self::ROLE_SUPER_ADMIN => [
            self::PERMISSION_VIEW_ENV,
            self::PERMISSION_EDIT_ENV,
            self::PERMISSION_DELETE_ENV,
            self::PERMISSION_BACKUP_ENV,
            self::PERMISSION_RESTORE_ENV,
            self::PERMISSION_REVEAL_SECRETS,
        ],
        self::ROLE_ADMIN => [
            self::PERMISSION_VIEW_ENV,
            self::PERMISSION_EDIT_ENV,
            self::PERMISSION_DELETE_ENV,
            self::PERMISSION_BACKUP_ENV,
            self::PERMISSION_RESTORE_ENV,
        ],
        self::ROLE_READ_ONLY => [
            self::PERMISSION_VIEW_ENV,
        ],
    ];

    /**
     * Determine whether the given user has the given permission.
     */
    public function check(mixed $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        // Production hard-lock: bypass_auth_in_local has no effect
        if (config('app.env') === 'production') {
            return $this->checkWithRoles($user, $permission);
        }

        // Local bypass (dev convenience only)
        if (config('environment-manager.bypass_auth_in_local', false)
            && config('app.env') === 'local') {
            return true;
        }

        // Custom authorization callback
        $callback = config('environment-manager.authorization_callback');
        if (is_callable($callback)) {
            return (bool) $callback($user, $permission);
        }

        // Default: allowed_users + role-based
        if (! $this->isAllowedUser($user)) {
            return false;
        }

        return $this->checkWithRoles($user, $permission);
    }

    private function checkWithRoles(mixed $user, string $permission): bool
    {
        $role = $this->getUserRole($user);

        // Handle reveal-secrets specially since it's not in admin's default array
        if ($permission === self::PERMISSION_REVEAL_SECRETS) {
            if ($role === self::ROLE_SUPER_ADMIN) {
                return true;
            }
            if ($role === self::ROLE_ADMIN) {
                return (bool) config('environment-manager.admin_can_reveal_secrets', false);
            }
            return false; // read_only and others
        }

        $permissions = self::$rolePermissions[$role] ?? [];

        return in_array($permission, $permissions, true);
    }

    /**
     * Determine the user's role within the env-manager context.
     * Override this by setting authorization_callback in config.
     */
    private function getUserRole(mixed $user): string
    {
        // Check if user has a getEnvManagerRole() method (optional package integration)
        if (method_exists($user, 'getEnvManagerRole')) {
            return $user->getEnvManagerRole();
        }

        // Check if user has is_super_admin or env_manager_role attribute
        if (isset($user->env_manager_role)) {
            return $user->env_manager_role;
        }

        // Fallback: first allowed user is super admin, rest are admins
        $allowedUsers = config('environment-manager.allowed_users');

        if (is_array($allowedUsers)) {
            $userId = $user->getKey();
            if (! empty($allowedUsers) && $allowedUsers[0] == $userId) {
                return self::ROLE_SUPER_ADMIN;
            }
        }

        return self::ROLE_ADMIN;
    }

    private function isAllowedUser(mixed $user): bool
    {
        $allowedUsers = config('environment-manager.allowed_users');

        if ($allowedUsers === null) {
            return true; // All authenticated users allowed
        }

        if (is_callable($allowedUsers)) {
            return (bool) $allowedUsers($user);
        }

        if (is_array($allowedUsers)) {
            return in_array($user->getKey(), $allowedUsers, false);
        }

        return false;
    }

    public function authorize(mixed $user, string $permission): void
    {
        if (! $this->check($user, $permission)) {
            abort(403, "Unauthorized. Missing permission: [{$permission}]");
        }
    }
}
