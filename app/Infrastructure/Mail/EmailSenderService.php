<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Infrastructure\Persistence\Eloquent\Models\EmailPlantillaModel;
use App\Infrastructure\Persistence\Eloquent\Models\NotificacionEnviadaModel;
use App\Infrastructure\Settings\EmailSettings;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailSenderService
{
    /**
     * Send an email using a stored DB template type.
     *
     * @param  string  $tipo     matches email_plantillas.tipo
     * @param  string  $toEmail
     * @param  string  $toNombre
     * @param  array   $vars     key => value map for {{variable}} replacement
     * @param  array   $context  optional: ['cliente_id', 'venta_id', 'cuota_id', 'enviado_por']
     */
    public function sendByTipo(
        string $tipo,
        string $toEmail,
        string $toNombre,
        array  $vars    = [],
        array  $context = []
    ): bool {
        $plantilla = EmailPlantillaModel::where('tipo', $tipo)
            ->where('activo', true)
            ->first();

        if (!$plantilla) {
            $this->logResult(
                tipo:    $tipo,
                email:   $toEmail,
                nombre:  $toNombre,
                asunto:  '',
                estado:  'FALLIDO',
                error:   "Plantilla '{$tipo}' no encontrada o inactiva.",
                context: $context
            );
            return false;
        }

        $asunto = $plantilla->renderAsunto($vars);
        $html   = $plantilla->renderCuerpo($vars);

        return $this->sendRaw(
            tipo:    $tipo,
            toEmail: $toEmail,
            toNombre: $toNombre,
            asunto:  $asunto,
            html:    $html,
            context: $context
        );
    }

    /**
     * Low-level send: configure SMTP at runtime, send, restore config.
     */
    public function sendRaw(
        string $tipo,
        string $toEmail,
        string $toNombre,
        string $asunto,
        string $html,
        array  $context = []
    ): bool {
        $config   = EmailSettings::get();
        $original = null;

        // ── Apply runtime SMTP config if activo=true ─────────────────────────
        if ($config && $config->activo) {
            $original = [
                'default'    => Config::get('mail.default'),
                'host'       => Config::get('mail.mailers.smtp.host'),
                'port'       => Config::get('mail.mailers.smtp.port'),
                'encryption' => Config::get('mail.mailers.smtp.encryption'),
                'username'   => Config::get('mail.mailers.smtp.username'),
                'password'   => Config::get('mail.mailers.smtp.password'),
                'from_addr'  => Config::get('mail.from.address'),
                'from_name'  => Config::get('mail.from.name'),
            ];

            Config::set('mail.default',                   'smtp');
            Config::set('mail.mailers.smtp.host',         $config->host);
            Config::set('mail.mailers.smtp.port',         $config->port);
            Config::set('mail.mailers.smtp.encryption',   $config->encryption);
            Config::set('mail.mailers.smtp.username',     $config->username);
            Config::set('mail.mailers.smtp.password',     $config->password);
            Config::set('mail.from.address',              $config->from_address);
            Config::set('mail.from.name',                 $config->from_name);
        }

        $success      = false;
        $errorMessage = null;

        try {
            Mail::to($toEmail, $toNombre)->send(new PlantillaMail($asunto, $html));
            $success = true;
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            Log::error('[EmailSenderService] Error al enviar email.', [
                'tipo'    => $tipo,
                'to'      => $toEmail,
                'error'   => $errorMessage,
            ]);
        } finally {
            // ── Always restore original mail config ───────────────────────────
            if ($original !== null) {
                Config::set('mail.default',                   $original['default']);
                Config::set('mail.mailers.smtp.host',         $original['host']);
                Config::set('mail.mailers.smtp.port',         $original['port']);
                Config::set('mail.mailers.smtp.encryption',   $original['encryption']);
                Config::set('mail.mailers.smtp.username',     $original['username']);
                Config::set('mail.mailers.smtp.password',     $original['password']);
                Config::set('mail.from.address',              $original['from_addr']);
                Config::set('mail.from.name',                 $original['from_name']);
            }
        }

        $this->logResult(
            tipo:    $tipo,
            email:   $toEmail,
            nombre:  $toNombre,
            asunto:  $asunto,
            estado:  $success ? 'ENVIADO' : 'FALLIDO',
            error:   $errorMessage,
            context: $context
        );

        return $success;
    }

    // ── Private: log every send attempt ──────────────────────────────────────
    private function logResult(
        string  $tipo,
        string  $email,
        string  $nombre,
        string  $asunto,
        string  $estado,
        ?string $error,
        array   $context
    ): void {
        try {
            NotificacionEnviadaModel::create([
                'tipo'                => $tipo,
                'cliente_id'          => $context['cliente_id']  ?? null,
                'venta_id'            => $context['venta_id']    ?? null,
                'cuota_id'            => $context['cuota_id']    ?? null,
                'destinatario_email'  => $email,
                'destinatario_nombre' => $nombre,
                'asunto'              => $asunto,
                'estado'              => $estado,
                'error_mensaje'       => $error,
                'enviado_por'         => $context['enviado_por'] ?? null,
                'enviado_en'          => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[EmailSenderService] No se pudo escribir el log de notificación.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
