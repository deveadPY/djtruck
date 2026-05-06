<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleExpenseModel extends Model
{
    use SoftDeletes;

    protected $table    = 'gastos_vehiculo';
    protected $fillable = [
        'vehiculo_id', 'origen_tipo', 'factura_proveedor_id', 'repuesto_id',
        'repuesto_cantidad', 'concepto', 'descripcion', 'categoria',
        'moneda', 'monto_moneda', 'tasa_cambio', 'monto_usd',
        'aplicado_al_costo', 'aplicado_en', 'fecha_gasto',
        'numero_remision', 'observaciones', 'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'monto_moneda'      => 'decimal:4',
        'tasa_cambio'       => 'decimal:8',
        'monto_usd'         => 'decimal:4',
        'aplicado_al_costo' => 'boolean',
        'aplicado_en'       => 'datetime',
        'fecha_gasto'       => 'date',
        'deleted_at'        => 'datetime',
    ];

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehiculo_id');
    }

    public function facturaProveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoiceModel::class, 'factura_proveedor_id');
    }
}
