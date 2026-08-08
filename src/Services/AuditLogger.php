<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Pradeepdev\EnvironmentManager\Models\EnvAuditLog;

class AuditLogger
{
    public function log(
        string $action,
        string $key,
        ?string $oldValue = null,
        ?string $newValue = null,
        bool $sensitive = false,
        string $source = 'ui',
    ): EnvAuditLog {
        $user = Auth::user();

        return EnvAuditLog::create([
            'action'     => $action,
            'key'        => $key,
            'old_value'  => $sensitive ? '[REDACTED]' : $oldValue,
            'new_value'  => $sensitive ? '[REDACTED]' : $newValue,
            'user_id'    => $user?->getKey(),
            'user_name'  => ($user instanceof Model ? $user->getAttribute('name') : null) ?? 'System',
            'source'     => $source,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    public function prune(int $days): int
    {
        return EnvAuditLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
