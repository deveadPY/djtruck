<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata'   => 'json',
        'created_at' => 'datetime',
    ];
}
