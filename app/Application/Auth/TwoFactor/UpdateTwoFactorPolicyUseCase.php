<?php

declare(strict_types=1);

namespace App\Application\Auth\TwoFactor;

use App\Infrastructure\Persistence\Eloquent\Models\EmpresaConfigModel;
use App\Infrastructure\Settings\EmpresaSettings;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/**
 * Activa o desactiva la autenticación de 2 pasos para todo el sistema.
 * Requiere la contraseña de quien hace el cambio y queda auditado.
 */
final class UpdateTwoFactorPolicyUseCase
{
    public function execute(User $actor, bool $enabled, string $password): void
    {
        if (!Hash::check($password, $actor->password)) {
            throw new RuntimeException('Contraseña actual incorrecta.');
        }

        $config = EmpresaConfigModel::firstOrCreate(['id' => 1]);
        $previous = (bool) ($config->two_factor_enabled ?? true);

        if ($previous === $enabled) {
            return;
        }

        $config->forceFill(['two_factor_enabled' => $enabled])->save();
        EmpresaSettings::forget();

        DB::table('audit_logs')->insert([
            'user_id'     => $actor->id,
            'action'      => $enabled ? '2FA_POLICY_ENABLED' : '2FA_POLICY_DISABLED',
            'entity_type' => 'configuracion_empresa',
            'entity_id'   => $config->id,
            'old_values'  => json_encode(['two_factor_enabled' => $previous]),
            'new_values'  => json_encode(['two_factor_enabled' => $enabled]),
            'metadata'    => null,
            'ip_address'  => request()?->ip(),
            'created_at'  => now(),
        ]);
    }
}
