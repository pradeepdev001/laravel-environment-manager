<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Pradeepdev\EnvironmentManager\Exceptions\BackupStorageException;

class BackupManager
{
    public function __construct(
        private readonly string $backupPath,
        private readonly int $retention,
        private readonly bool $encryption,
    ) {}

    /**
     * Create a backup of the given .env file.
     * Returns the full path to the backup file created.
     */
    public function create(string $envPath): string
    {
        $this->ensureBackupDirectory();

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            throw new \RuntimeException("Could not read .env file at [{$envPath}] for backup.");
        }

        $timestamp = now()->format('Y_m_d_His');
        $hash = substr(md5($contents . microtime()), 0, 8);
        $filename = "env_backup_{$timestamp}_{$hash}.env";
        $destination = rtrim($this->backupPath, '/') . '/' . $filename;

        if ($this->encryption) {
            $contents = $this->encrypt($contents);
            $filename .= '.enc';
            $destination .= '.enc';
        }

        file_put_contents($destination, $contents);

        $this->enforceRetention();

        return $destination;
    }

    /**
     * Restore a backup file to the given .env path.
     */
    public function restore(string $backupFilePath, string $envPath): void
    {
        if (! file_exists($backupFilePath)) {
            throw new \RuntimeException("Backup file not found at [{$backupFilePath}].");
        }

        $contents = file_get_contents($backupFilePath);
        if ($contents === false) {
            throw new \RuntimeException("Could not read backup file at [{$backupFilePath}].");
        }

        if ($this->encryption && str_ends_with($backupFilePath, '.enc')) {
            $contents = $this->decrypt($contents);
        }

        // Atomic write to destination
        $tmp = tempnam(dirname($envPath), '.env_restore_tmp_');
        file_put_contents($tmp, $contents);
        rename($tmp, $envPath);
    }

    /**
     * List all backup files in the backup directory, newest first.
     *
     * @return array<int, array{filename: string, path: string, size: int, created_at: string}>
     */
    public function list(): array
    {
        $this->ensureBackupDirectory();

        $files = glob(rtrim($this->backupPath, '/') . '/env_backup_*.env*');
        if ($files === false) {
            return [];
        }

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'filename'   => basename($file),
                'path'       => $file,
                'size'       => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Sort newest first
        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * Delete a single backup file.
     */
    public function delete(string $backupFilePath): void
    {
        if (file_exists($backupFilePath)) {
            unlink($backupFilePath);
        }
    }

    /**
     * Get raw contents of a backup (decrypted if needed).
     */
    public function getContents(string $backupFilePath): string
    {
        if (! file_exists($backupFilePath)) {
            throw new \RuntimeException("Backup file not found at [{$backupFilePath}].");
        }

        $contents = file_get_contents($backupFilePath);
        if ($contents === false) {
            throw new \RuntimeException("Could not read backup file.");
        }

        if ($this->encryption && str_ends_with($backupFilePath, '.enc')) {
            return $this->decrypt($contents);
        }

        return $contents;
    }

    private function ensureBackupDirectory(): void
    {
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0750, true);
        }

        if (! is_writable($this->backupPath)) {
            throw BackupStorageException::notWritable($this->backupPath);
        }
    }

    private function enforceRetention(): void
    {
        $backups = $this->list();

        if (count($backups) <= $this->retention) {
            return;
        }

        // Delete oldest backups beyond retention limit
        $toDelete = array_slice($backups, $this->retention);
        foreach ($toDelete as $backup) {
            try {
                $this->delete($backup['path']);
            } catch (\Throwable $e) {
                error_log("EnvironmentManager: Could not delete old backup [{$backup['path']}]: {$e->getMessage()}");
            }
        }
    }

    private function encrypt(string $data): string
    {
        $key = base64_decode(substr(config('app.key', ''), 7));
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $data): string
    {
        $key = base64_decode(substr(config('app.key', ''), 7));
        $raw = base64_decode($data);
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);

        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Failed to decrypt backup file. The APP_KEY may have changed.');
        }

        return $decrypted;
    }
}
