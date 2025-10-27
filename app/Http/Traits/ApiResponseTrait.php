<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponseTrait
{
    /**
     * Format a successful API response.
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function success(mixed $data, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Format an error API response.
     *
     * @param string $message
     * @param int $statusCode
     * @param string $errorCode
     * @param array $details
     * @param string|null $path
     * @param string|null $traceId
     * @return JsonResponse
     */
    protected function error(
        string $message = 'Error',
        int $statusCode = 400,
        string $errorCode = 'GENERIC_ERROR',
        array $details = [],
        ?string $path = null,
        ?string $traceId = null
    ): JsonResponse {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
                'timestamp' => now()->toIso8601String(),
                'path' => $path ?? request()->fullUrl(),
                'traceId' => $traceId ?? (string) \Illuminate\Support\Str::uuid(),
            ],
        ], $statusCode);
    }

    /**
     * Format a paginated API response.
     *
     * @param LengthAwarePaginator|ResourceCollection $paginatedData
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function paginate(LengthAwarePaginator|ResourceCollection $paginatedData, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $data = $paginatedData instanceof ResourceCollection ? $paginatedData->toArray(request()) : $paginatedData->items();

        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'currentPage' => $paginatedData->currentPage(),
                'totalPages' => $paginatedData->lastPage(),
                'totalItems' => $paginatedData->total(),
                'itemsPerPage' => $paginatedData->perPage(),
                'hasNext' => $paginatedData->hasMorePages(),
                'hasPrevious' => $paginatedData->currentPage() > 1,
            ],
            'links' => [
                'self' => $paginatedData->url($paginatedData->currentPage()),
                'next' => $paginatedData->nextPageUrl(),
                'first' => $paginatedData->url(1),
                'last' => $paginatedData->url($paginatedData->lastPage()),
            ],
        ], $statusCode);
    }
}
