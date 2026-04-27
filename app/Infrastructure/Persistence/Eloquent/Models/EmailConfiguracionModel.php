<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class EmailConfiguracionModel extends Model
{
    protected $table = 'email_configuracion';

    protected $fillable = [
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'activo',
        'updated_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'port'   => 'integer',
    ];

    // ── Accessor: auto-decrypt password when reading ─────────────────────────
    public function getPasswordAttribute(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return Crypt::decrypt($value);
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Mutator: auto-encrypt password when writing ──────────────────────────
    public function setPasswordAttribute(?string $value): void
    {
        $this->attributes['password'] = $value ? Crypt::encrypt($value) : null;
    }
}
