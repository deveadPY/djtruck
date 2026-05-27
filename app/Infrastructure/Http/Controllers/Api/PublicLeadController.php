<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Leads\CaptureLeadDTO;
use App\Application\Leads\CaptureLeadUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint público (sin auth) para que el catálogo web capture leads.
 * Protegido por rate limiter para evitar spam.
 *
 * POST /api/v1/public/leads
 */
final class PublicLeadController extends BaseApiController
{
    public function store(Request $request, CaptureLeadUseCase $useCase): JsonResponse
    {
        $data = $request->validate([
            'nombre'       => 'required|string|max:100',
            'telefono'     => 'required|string|max:50',
            'email'        => 'nullable|email|max:150',
            'vehiculo_id'  => 'nullable|integer|exists:vehiculos,id',
            'canal'        => 'nullable|in:WhatsApp,Formulario',
            'mensaje'      => 'nullable|string|max:2000',
        ]);

        $dto = CaptureLeadDTO::fromArray(array_merge($data, [
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
        ]));

        $lead = $useCase->execute($dto);

        return $this->successResponse([
            'id'     => $lead->getId()->value(),
            'estado' => $lead->getEstado()->value,
        ], 'Consulta recibida. Te contactaremos pronto.');
    }
}
