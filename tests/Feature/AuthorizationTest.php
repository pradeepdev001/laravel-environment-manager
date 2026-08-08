<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Pradeepdev\EnvironmentManager\Authorization\EnvManagerGate;
use Symfony\Component\HttpKernel\Exception\HttpException;

// Minimal stub user that implements Authenticatable
class StubUser extends User
{
    public string $env_manager_role = 'admin';

    public int $stubId = 1;

    public function __construct(string $role = 'admin', int $id = 1)
    {
        $this->env_manager_role = $role;
        $this->stubId           = $id;
    }

    public function getKey(): int
    {
        return $this->stubId;
    }

    public function getAuthIdentifier(): int
    {
        return $this->stubId;
    }
}

beforeEach(function () {
    $this->gate = new EnvManagerGate;
    config(['environment-manager.bypass_auth_in_local' => false]);
    config(['app.env' => 'testing']);
    config(['environment-manager.allowed_users' => null]);
    config(['environment-manager.authorization_callback' => null]);
    config(['environment-manager.admin_can_reveal_secrets' => false]);
});

// Super Admin — all permissions
it('super admin has all permissions', function () {
    $user = new StubUser('super_admin');

    foreach ([
        EnvManagerGate::PERMISSION_VIEW_ENV,
        EnvManagerGate::PERMISSION_EDIT_ENV,
        EnvManagerGate::PERMISSION_DELETE_ENV,
        EnvManagerGate::PERMISSION_BACKUP_ENV,
        EnvManagerGate::PERMISSION_RESTORE_ENV,
        EnvManagerGate::PERMISSION_REVEAL_SECRETS,
    ] as $permission) {
        expect($this->gate->check($user, $permission))
            ->toBeTrue("Expected super_admin to have [{$permission}]");
    }
});

// Admin
it('admin has view, edit, delete, backup, restore permissions', function () {
    $user = new StubUser('admin');

    foreach ([
        EnvManagerGate::PERMISSION_VIEW_ENV,
        EnvManagerGate::PERMISSION_EDIT_ENV,
        EnvManagerGate::PERMISSION_DELETE_ENV,
        EnvManagerGate::PERMISSION_BACKUP_ENV,
        EnvManagerGate::PERMISSION_RESTORE_ENV,
    ] as $permission) {
        expect($this->gate->check($user, $permission))
            ->toBeTrue("Expected admin to have [{$permission}]");
    }
});

it('admin cannot reveal secrets by default', function () {
    $user = new StubUser('admin');

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_REVEAL_SECRETS))->toBeFalse();
});

it('admin can reveal secrets when configured', function () {
    config(['environment-manager.admin_can_reveal_secrets' => true]);
    $user = new StubUser('admin');

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_REVEAL_SECRETS))->toBeTrue();
});

// Read Only
it('read_only user can only view', function () {
    $user = new StubUser('read_only');

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_VIEW_ENV))->toBeTrue();
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_EDIT_ENV))->toBeFalse();
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_DELETE_ENV))->toBeFalse();
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_REVEAL_SECRETS))->toBeFalse();
});

// Null user
it('null user is denied all permissions', function () {
    expect($this->gate->check(null, EnvManagerGate::PERMISSION_VIEW_ENV))->toBeFalse();
    expect($this->gate->check(null, EnvManagerGate::PERMISSION_EDIT_ENV))->toBeFalse();
});

// Allowed users list
it('user not in allowed_users list is denied', function () {
    config(['environment-manager.allowed_users' => [99, 100]]);
    $user = new StubUser('super_admin', 1);

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_VIEW_ENV))->toBeFalse();
});

it('user in allowed_users list is granted based on role', function () {
    config(['environment-manager.allowed_users' => [1]]);
    $user = new StubUser('admin', 1);

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_VIEW_ENV))->toBeTrue();
});

// Custom callback
it('custom authorization_callback overrides role system', function () {
    config(['environment-manager.authorization_callback' => fn ($u, $p) => $p === 'view-env']);
    $user = new StubUser('read_only');

    expect($this->gate->check($user, EnvManagerGate::PERMISSION_VIEW_ENV))->toBeTrue();
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_EDIT_ENV))->toBeFalse();
});

// authorize() aborts on denial
it('authorize() throws 403 when permission denied', function () {
    $user = new StubUser('read_only');
    $this->gate->authorize($user, EnvManagerGate::PERMISSION_EDIT_ENV);
})->throws(HttpException::class);

// Bypass in local
it('bypass_auth_in_local grants all permissions in local env', function () {
    config(['environment-manager.bypass_auth_in_local' => true]);
    config(['app.env' => 'local']);

    $user = new StubUser('read_only');
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_DELETE_ENV))->toBeTrue();
});

it('bypass_auth_in_local has no effect in production', function () {
    config(['environment-manager.bypass_auth_in_local' => true]);
    config(['app.env' => 'production']);

    $user = new StubUser('read_only');
    expect($this->gate->check($user, EnvManagerGate::PERMISSION_DELETE_ENV))->toBeFalse();
});
