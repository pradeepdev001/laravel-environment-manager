<?php

declare(strict_types=1);

namespace Pradeepdev\EnvironmentManager\Models;

use Illuminate\Database\Eloquent\Model;

class EnvVersionHistory extends Model
{
    protected $table = 'env_version_history';

    protected $fillable = [
        'action',
        'key',
        'old_value',
        'new_value',
        'reason',
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
