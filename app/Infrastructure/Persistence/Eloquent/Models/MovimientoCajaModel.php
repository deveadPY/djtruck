<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCajaModel extends Model
{
    protected $table    = 'movimientos_caja';
    protected $fillable = [
        'caja_id', 'tipo', 'concepto', 'moneda', 'monto', 'monto_usd',
        'tasa_cambio', 'referencia', 'venta_id', 'cuota_id',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'monto'      => 'decimal:4',
        'monto_usd'  => 'decimal:4',
        'tasa_cambio'=> 'decimal:8',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(CajaModel::class, 'caja_id');
    }
}
