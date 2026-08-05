<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Models;

use Illuminate\Database\Eloquent\Model;

class EnvAuditLog extends Model
{
    protected $table = 'env_audit_logs';

    protected $fillable = [
        'action',
        'key',
        'old_value',
        'new_value',
        'user_id',
        'user_name',
        'source',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
