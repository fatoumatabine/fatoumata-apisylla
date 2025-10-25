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
     * @param array $errors
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', int $statusCode = 400, array $errors = []): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
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
