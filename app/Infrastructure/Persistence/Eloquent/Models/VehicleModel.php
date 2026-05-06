<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModel extends Model
{
    use SoftDeletes;

    protected $table      = 'vehiculos';
    protected $primaryKey = 'id';

    protected $fillable = [
        'numero_chasis', 'numero_motor', 'numero_serie', 'marca', 'modelo',
        'anio', 'color', 'tipo_vehiculo', 'capacidad_toneladas', 'anio_fabricacion',
        'pais_origen', 'kilometraje', 'estado', 'ubicacion',
        'moneda_costo', 'costo_origen_usd', 'costo_origen_moneda', 'tasa_cambio_compra',
        'total_gastos_usd', 'precio_venta_sugerido_usd', 'margen_objetivo_pct',
        'precio_contado_usd', 'precio_cuotas_usd',
        'valor_toma_usd', 'proveedor_id', 'factura_compra_numero', 'factura_compra_fecha',
        'venta_canje_origen_id', 'vehiculo_canje_ref_id',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'costo_origen_usd'          => 'decimal:4',
        'costo_origen_moneda'       => 'decimal:4',
        'tasa_cambio_compra'        => 'decimal:8',
        'total_gastos_usd'          => 'decimal:4',
        'valor_libro_usd'           => 'decimal:4',
        'valor_toma_usd'            => 'decimal:4',
        'precio_venta_sugerido_usd' => 'decimal:4',
        'precio_contado_usd'        => 'decimal:4',
        'precio_cuotas_usd'         => 'decimal:4',
        'margen_objetivo_pct'       => 'decimal:2',
        'factura_compra_fecha'      => 'date',
        'deleted_at'                => 'datetime',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(VehicleExpenseModel::class, 'vehiculo_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(VehicleImageModel::class, 'vehiculo_id')->orderBy('orden');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'proveedor_id');
    }

    public function ventaOrigen(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_canje_origen_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(SaleModel::class, 'vehiculo_id');
    }

    public function isDisponible(): bool
    {
        return in_array($this->estado, ['DISPONIBLE', 'RESERVADO']);
    }

    public function getValorLibroAttribute(): float
    {
        return (float)($this->costo_origen_usd + $this->total_gastos_usd);
    }
}
