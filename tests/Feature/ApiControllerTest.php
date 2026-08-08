<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Pradeepdev\EnvironmentManager\EnvManager;

beforeEach(function () {
    $this->writeTestEnv("APP_NAME=Laravel\nAPP_ENV=local\nDB_PASSWORD=secret\n");

    // Grant all permissions to everyone during tests
    config(['environment-manager.bypass_auth_in_local' => true]);
    config(['app.env' => 'local']);

    $this->user = User::forceCreate([
        'name'     => 'Test User',
        'email'    => uniqid('user').'@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('GET /env returns variable list', function () {
    $this->actingAs($this->user, 'admin')
        ->getJson('/'.config('environment-manager.api_prefix').'/env')
        ->assertOk()
        ->assertJsonStructure(['success', 'data', 'message'])
        ->assertJson(['success' => true]);
});

it('GET /env masks sensitive values', function () {
    $response = $this->actingAs($this->user, 'admin')
        ->getJson('/'.config('environment-manager.api_prefix').'/env');

    $data = collect($response->json('data'))->keyBy('key');
    expect($data['DB_PASSWORD']['value'])->toBe('••••••••');
});

it('POST /env creates a new variable', function () {
    $this->actingAs($this->user, 'admin')
        ->postJson('/'.config('environment-manager.api_prefix').'/env', [
            'key'   => 'NEW_FLAG',
            'value' => 'true',
        ])
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    expect(app(EnvManager::class)->get('NEW_FLAG')?->rawValue)
        ->toBe('true');
});

it('POST /env returns 422 for invalid value', function () {
    $this->actingAs($this->user, 'admin')
        ->postJson('/'.config('environment-manager.api_prefix').'/env', [
            'key'   => 'APP_URL',
            'value' => 'not-a-url',
        ])
        ->assertStatus(422)
        ->assertJsonStructure(['success', 'errors']);
});

it('PUT /env/{key} updates a variable', function () {
    $this->actingAs($this->user, 'admin')
        ->putJson('/'.config('environment-manager.api_prefix').'/env/APP_NAME', [
            'value' => 'Updated App',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(app(EnvManager::class)->get('APP_NAME')?->rawValue)
        ->toBe('Updated App');
});

it('PUT /env/{key} returns 404 for missing key', function () {
    $this->actingAs($this->user, 'admin')
        ->putJson('/'.config('environment-manager.api_prefix').'/env/NONEXISTENT', ['value' => 'x'])
        ->assertNotFound();
});

it('DELETE /env/{key} deletes a variable', function () {
    $this->actingAs($this->user, 'admin')
        ->deleteJson('/'.config('environment-manager.api_prefix').'/env/APP_ENV')
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(app(EnvManager::class)->get('APP_ENV'))->toBeNull();
});

it('DELETE /env/{key} returns 404 for missing key', function () {
    $this->actingAs($this->user, 'admin')
        ->deleteJson('/'.config('environment-manager.api_prefix').'/env/MISSING_KEY')
        ->assertNotFound();
});

it('GET /env/history returns records', function () {
    app(EnvManager::class)->set('APP_NAME', 'Changed');

    $this->actingAs($this->user, 'admin')
        ->getJson('/'.config('environment-manager.api_prefix').'/env/history')
        ->assertOk()
        ->assertJsonStructure(['success', 'data']);
});

it('GET /env/backups returns backup list', function () {
    $this->actingAs($this->user, 'admin')
        ->getJson('/'.config('environment-manager.api_prefix').'/env/backups')
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('unauthenticated request returns 401', function () {
    // No actingAs — route has auth middleware
    $this->getJson('/'.config('environment-manager.api_prefix').'/env')
        ->assertStatus(401);
});
