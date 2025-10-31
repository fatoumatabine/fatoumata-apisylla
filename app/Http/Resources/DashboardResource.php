<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="DashboardResource",
 *     title="Dashboard Resource",
 *     description="Représentation du dashboard pour admin ou client",
 *     @OA\Property(property="total_comptes", type="integer", example=25),
 *     @OA\Property(property="balance", type="number", format="float", example=150000.50),
 *     @OA\Property(property="total_transactions", type="integer", example=150),
 *     @OA\Property(property="recent_transactions", type="array", @OA\Items(ref="#/components/schemas/TransactionResource")),
 *     @OA\Property(property="comptes_today", type="integer", example=3, description="Pour admin uniquement"),
 *     @OA\Property(property="comptes", type="array", @OA\Items(ref="#/components/schemas/CompteResource"), description="Pour client uniquement")
 * )
 */
class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'total_comptes' => $this->resource['total_comptes'] ?? 0,
            'balance' => $this->resource['balance'] ?? 0,
            'total_transactions' => $this->resource['total_transactions'] ?? 0,
            'recent_transactions' => TransactionResource::collection($this->resource['recent_transactions'] ?? []),
            'comptes_today' => $this->resource['comptes_today'] ?? null,
            'comptes' => isset($this->resource['comptes']) ? CompteResource::collection($this->resource['comptes']) : null,
        ];
    }
}
