<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    protected function successResponse(mixed $data, string $message = 'OK', int $status = 200): JsonResponse
    {
        $payload = $data instanceof JsonResource ? $data->resolve() : $data;

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $payload,
        ], $status);
    }

    protected function errorResponse(string $message, mixed $errors = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @param class-string<JsonResource>|null $resourceClass  Transform each item through this Resource when provided
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $message = 'OK', ?string $resourceClass = null): JsonResponse
    {
        $items = $resourceClass
            ? $resourceClass::collection($paginator->getCollection())->resolve()
            : $paginator->items();

        return response()->json([
            'success'    => true,
            'message'    => $message,
            'data'       => $items,
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }
}
