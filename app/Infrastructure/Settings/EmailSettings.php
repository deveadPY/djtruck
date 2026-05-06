<?php

declare(strict_types=1);

namespace App\Infrastructure\Settings;

use App\Infrastructure\Persistence\Eloquent\Models\EmailConfiguracionModel;
use Illuminate\Support\Facades\Cache;

class EmailSettings
{
    /**
     * Returns the active email configuration, or null if none is active.
     * Cached for 1 hour.
     */
    public static function get(): ?EmailConfiguracionModel
    {
        return Cache::remember('email_config', 3600, function () {
            return EmailConfiguracionModel::where('activo', true)->first();
        });
    }

    /**
     * Flush the cached email configuration (call after saving changes).
     */
    public static function forget(): void
    {
        Cache::forget('email_config');
    }
}
