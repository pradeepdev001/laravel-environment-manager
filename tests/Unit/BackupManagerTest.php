<?php

declare(strict_types=1);

use Pradeepdev\EnvironmentManager\Services\BackupManager;

beforeEach(function () {
    $this->backupDir = sys_get_temp_dir().'/env_backup_test_'.getmypid();
    $this->envFile   = sys_get_temp_dir().'/test_env_'.getmypid().'.env';

    file_put_contents($this->envFile, "APP_NAME=Laravel\nAPP_ENV=local\n");

    $this->manager = new BackupManager($this->backupDir, 5, false);
});

afterEach(function () {
    if (is_dir($this->backupDir)) {
        array_map('unlink', glob($this->backupDir.'/*') ?: []);
        @rmdir($this->backupDir);
    }
    if (file_exists($this->envFile)) {
        @unlink($this->envFile);
    }
});

it('creates a backup file', function () {
    $path = $this->manager->create($this->envFile);

    expect(file_exists($path))->toBeTrue()
        ->and(file_get_contents($path))->toContain('APP_NAME=Laravel');
});

it('names backup file with timestamp and hash', function () {
    $path = $this->manager->create($this->envFile);

    expect(basename($path))->toMatch('/^env_backup_\d{4}_\d{2}_\d{2}_\d{6}_[a-f0-9]{8}\.env$/');
});

it('lists backups newest first', function () {
    $this->manager->create($this->envFile);
    usleep(1100000); // ensure different second
    $this->manager->create($this->envFile);

    $backups = $this->manager->list();

    expect($backups)->toHaveCount(2)
        ->and($backups[0]['created_at'] >= $backups[1]['created_at'])->toBeTrue();
});

it('enforces retention limit', function () {
    $manager = new BackupManager($this->backupDir, 2, false);

    $manager->create($this->envFile);
    usleep(500000);
    $manager->create($this->envFile);
    usleep(500000);
    $manager->create($this->envFile);

    expect($manager->list())->toHaveCount(2);
});

it('restores a backup to the env file', function () {
    $backupPath = $this->manager->create($this->envFile);

    file_put_contents($this->envFile, "APP_NAME=Changed\n");

    $this->manager->restore($backupPath, $this->envFile);

    expect(file_get_contents($this->envFile))->toContain('APP_NAME=Laravel');
});

it('deletes a backup file', function () {
    $backupPath = $this->manager->create($this->envFile);

    expect(file_exists($backupPath))->toBeTrue();

    $this->manager->delete($backupPath);

    expect(file_exists($backupPath))->toBeFalse();
});

it('reads backup contents', function () {
    $backupPath = $this->manager->create($this->envFile);
    $contents   = $this->manager->getContents($backupPath);

    expect($contents)->toContain('APP_NAME=Laravel');
});

it('throws BackupStorageException if directory is not writable', function () {
    $manager = new BackupManager('/root/no_access_'.getmypid(), 5, false);
    $manager->create($this->envFile);
})->throws(Exception::class);

it('throws exception for missing backup file on restore', function () {
    $this->manager->restore('/nonexistent/backup.env', $this->envFile);
})->throws(RuntimeException::class);
