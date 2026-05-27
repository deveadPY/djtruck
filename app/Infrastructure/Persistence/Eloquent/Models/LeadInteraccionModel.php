<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInteraccionModel extends Model
{
    protected $table    = 'lead_interacciones';
    protected $fillable = [
        'lead_id', 'tipo', 'asunto', 'descripcion', 'resultado',
        'fecha_interaccion', 'proximo_seguimiento', 'created_by',
    ];

    protected $casts = [
        'fecha_interaccion'   => 'datetime',
        'proximo_seguimiento' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(LeadModel::class, 'lead_id');
    }
}
