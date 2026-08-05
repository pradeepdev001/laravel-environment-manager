<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Commands;

use Illuminate\Console\Command;
use Pradeepdev\EnvironmentManager\Services\AuditLogger;
use Pradeepdev\EnvironmentManager\Services\VersionHistory;

class PruneCommand extends Command
{
    protected $signature = 'env-manager:prune
        {--history-days= : Override version history retention days}
        {--audit-days=   : Override audit log retention days}';

    protected $description = 'Prune old version history and audit log records based on configured retention.';

    public function handle(VersionHistory $history, AuditLogger $auditLogger): int
    {
        $historyDays = (int) ($this->option('history-days')
            ?? config('environment-manager.version_history_retention_days', 90));

        $auditDays = (int) ($this->option('audit-days')
            ?? config('environment-manager.audit_log_retention_days', 180));

        $historyDeleted = $history->prune($historyDays);
        $auditDeleted   = $auditLogger->prune($auditDays);

        $this->info("Pruned {$historyDeleted} version history records older than {$historyDays} days.");
        $this->info("Pruned {$auditDeleted} audit log records older than {$auditDays} days.");

        return self::SUCCESS;
    }
}
