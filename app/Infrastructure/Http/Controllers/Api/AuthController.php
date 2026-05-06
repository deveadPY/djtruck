<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

final class AuthController extends BaseApiController
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated)) {
            return $this->errorResponse('Credenciales inválidas.', null, 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('erp-api')->plainTextToken;

        return $this->successResponse([
            'user'  => $user,
            'token' => $token,
        ], 'Autenticado correctamente.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Sesión cerrada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse($request->user());
    }
}
