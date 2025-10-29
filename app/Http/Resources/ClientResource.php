<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="ClientResource",
 *     title="Client Resource",
 *     description="Représentation d'un client",
 *     @OA\Property(property="id", type="string", readOnly="true", example="uuid"),
 *     @OA\Property(property="titulaire", type="string", readOnly="true", example="John Doe"),
 *     @OA\Property(property="nci", type="string", readOnly="true", example="1234567890123"),
 *     @OA\Property(property="email", type="string", readOnly="true", example="john.doe@example.com"),
 *     @OA\Property(property="telephone", type="string", readOnly="true", example="771234567"),
 *     @OA\Property(property="adresse", type="string", readOnly="true", example="Dakar, Sénégal")
 * )
 */
class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulaire' => $this->titulaire,
            'nci' => $this->nci,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
        ];
    }
}
