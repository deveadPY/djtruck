<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoItemModel extends Model
{
    use SoftDeletes;

    protected $table    = 'presupuesto_items';
    protected $fillable = [
        'presupuesto_id', 'itemable_id', 'itemable_type', 'descripcion',
        'cantidad', 'precio_unitario_usd', 'subtotal_usd',
    ];

    protected $casts = [
        'cantidad'            => 'decimal:3',
        'precio_unitario_usd' => 'decimal:4',
        'subtotal_usd'        => 'decimal:4',
        'deleted_at'          => 'datetime',
    ];

    public function presupuesto(): BelongsTo
    {
        return $this->belongsTo(PresupuestoModel::class, 'presupuesto_id');
    }
}
