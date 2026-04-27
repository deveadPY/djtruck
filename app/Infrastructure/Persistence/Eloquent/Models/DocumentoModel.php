<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DocumentoModel extends Model
{
    use SoftDeletes;

    protected $table    = 'documentos';
    protected $fillable = [
        'documentable_type', 'documentable_id',
        'ruta', 'nombre_original', 'mime_type', 'tamano_bytes',
        'created_by', 'updated_by', 'deleted_by',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'deleted_at'   => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return asset($this->ruta);
    }

    public function getTamanoFormateadoAttribute(): string
    {
        $bytes = $this->tamano_bytes;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
