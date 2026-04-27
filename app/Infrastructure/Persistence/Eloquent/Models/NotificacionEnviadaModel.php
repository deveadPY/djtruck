<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionEnviadaModel extends Model
{
    protected $table      = 'notificaciones_enviadas';
    public    $timestamps = false;   // only enviado_en — no created_at/updated_at

    protected $fillable = [
        'tipo',
        'cliente_id',
        'venta_id',
        'cuota_id',
        'destinatario_email',
        'destinatario_nombre',
        'asunto',
        'estado',
        'error_mensaje',
        'enviado_por',
        'enviado_en',
    ];

    protected $casts = [
        'enviado_en' => 'datetime',
    ];
}
