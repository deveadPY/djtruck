<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseModel extends Model
{
    use SoftDeletes;

    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id',
        'numero_factura',
        'fecha_compra',
        'moneda_compra',
        'monto_total_moneda',
        'monto_total_usd',
        'tasa_cambio',
        'observaciones',
        'estado',
        'caja_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'monto_total_moneda' => 'decimal:2',
        'monto_total_usd'    => 'decimal:2',
        'tasa_cambio'        => 'decimal:2',
        'fecha_compra'       => 'date',
        'deleted_at'         => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'proveedor_id');
    }
}
