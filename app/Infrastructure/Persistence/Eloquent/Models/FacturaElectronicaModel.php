<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaElectronicaModel extends Model
{
    use SoftDeletes;

    protected $table = 'facturas_electronicas';

    protected $fillable = [
        'venta_id', 'tipo_documento', 'numero_documento', 'cdc', 'provider', 'estado',
        'total_neto', 'total_iva', 'total_general', 'moneda',
        'url_pdf', 'url_xml', 'qr_code',
        'error_code', 'error_message', 'raw_response',
        'emitida_at', 'aprobada_at', 'cancelada_at', 'motivo_cancelacion',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'total_neto'    => 'decimal:4',
        'total_iva'     => 'decimal:4',
        'total_general' => 'decimal:4',
        'raw_response'  => 'array',
        'emitida_at'    => 'datetime',
        'aprobada_at'   => 'datetime',
        'cancelada_at'  => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(SaleModel::class, 'venta_id');
    }

    public function isAprobada(): bool
    {
        return $this->estado === 'APROBADO';
    }

    public function isCancelada(): bool
    {
        return $this->estado === 'CANCELADO';
    }

    public function puedeReintentar(): bool
    {
        return in_array($this->estado, ['ERROR', 'RECHAZADO'], true);
    }
}
