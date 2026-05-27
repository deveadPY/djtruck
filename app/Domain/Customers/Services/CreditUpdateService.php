<?php

declare(strict_types=1);

namespace App\Domain\Customers\Services;

use App\Domain\Customers\Repositories\CustomerRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Servicio de dominio para modificar la línea de crédito de un cliente,
 * con auditoría automática del cambio.
 */
final class CreditUpdateService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $repository
    ) {}

    public function updateLimit(int $customerId, float $nuevoLimiteUsd, ?string $motivo = null): void
    {
        if ($nuevoLimiteUsd < 0) {
            throw new RuntimeException('La línea de crédito no puede ser negativa.');
        }

        $cliente = $this->repository->findById($customerId);
        if (!$cliente) {
            throw new RuntimeException("Cliente {$customerId} no encontrado.");
        }

        $oldLimit = $cliente->getLineaCreditoUsd();

        DB::transaction(function () use ($customerId, $nuevoLimiteUsd, $oldLimit, $motivo) {
            DB::table('clientes')
                ->where('id', $customerId)
                ->update([
                    'linea_credito_usd' => $nuevoLimiteUsd,
                    'updated_by'        => Auth::id(),
                    'updated_at'        => now(),
                ]);

            DB::table('audit_logs')->insert([
                'user_id'     => Auth::id(),
                'action'      => 'CREDIT_LIMIT_UPDATE',
                'entity_type' => 'cliente',
                'entity_id'   => $customerId,
                'old_values'  => json_encode(['linea_credito_usd' => $oldLimit]),
                'new_values'  => json_encode(['linea_credito_usd' => $nuevoLimiteUsd]),
                'metadata'    => json_encode(['motivo' => $motivo]),
                'ip_address'  => request()?->ip(),
                'created_at'  => now(),
            ]);
        });
    }
}
