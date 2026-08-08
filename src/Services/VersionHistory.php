<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Pradeepdev\EnvironmentManager\Models\EnvVersionHistory;

class VersionHistory
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Record a single variable change.
     */
    public function record(
        string $action,
        string $key,
        ?string $oldValue,
        ?string $newValue,
        bool $sensitive = false,
        ?string $reason = null,
        string $source = 'ui',
    ): EnvVersionHistory {
        $user = Auth::user();

        $record = EnvVersionHistory::create([
            'action'     => $action,
            'key'        => $key,
            'old_value'  => $sensitive ? '[REDACTED]' : $oldValue,
            'new_value'  => $sensitive ? '[REDACTED]' : $newValue,
            'reason'     => $reason,
            'user_id'    => $user?->getKey(),
            'user_name'  => ($user instanceof Model ? $user->getAttribute('name') : null) ?? 'System',
            'source'     => $source,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);

        // Also log to audit log
        $this->auditLogger->log(
            action: $action,
            key: $key,
            oldValue: $oldValue,
            newValue: $newValue,
            sensitive: $sensitive,
            source: $source,
        );

        return $record;
    }

    /**
     * Record a bulk change involving multiple variables.
     *
     * @param  array<string, array{old: ?string, new: ?string, sensitive: bool}>  $changes
     */
    public function recordBulk(
        array $changes,
        ?string $reason = null,
        string $source = 'ui',
    ): void {
        foreach ($changes as $key => $change) {
            $this->record(
                action: 'bulk_update',
                key: $key,
                oldValue: $change['old'],
                newValue: $change['new'],
                sensitive: $change['sensitive'] ?? false,
                reason: $reason,
                source: $source,
            );
        }
    }

    /**
     * Prune records older than $days days.
     */
    public function prune(int $days): int
    {
        return EnvVersionHistory::where('created_at', '<', now()->subDays($days))->delete();
    }
}
