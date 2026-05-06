<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceModel extends Model
{
    use SoftDeletes;

    protected $table    = 'facturas_proveedores';
    protected $fillable = [
        'proveedor_id', 'numero_factura', 'fecha_factura', 'destino',
        'vehiculo_id', 'cuenta_gasto', 'moneda', 'subtotal', 'impuestos',
        'total_usd', 'estado', 'descripcion', 'created_by',
    ];

    protected $casts = [
        'subtotal'      => 'decimal:4',
        'impuestos'     => 'decimal:4',
        'total'         => 'decimal:4',
        'total_usd'     => 'decimal:4',
        'fecha_factura' => 'date',
        'deleted_at'    => 'datetime',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'proveedor_id');
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }
}
