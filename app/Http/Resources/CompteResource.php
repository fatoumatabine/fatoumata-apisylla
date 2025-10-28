<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CompteResource",
 *     title="Compte Resource",
 *     description="Représentation d'un compte bancaire",
 *     @OA\Property(property="id", type="integer", readOnly="true", example=1),
 *     @OA\Property(property="numeroCompte", type="string", readOnly="true", example="C12345678"),
 *     @OA\Property(property="titulaire", type="string", readOnly="true", example="John Doe"),
 *     @OA\Property(property="type", type="string", readOnly="true", example="courant"),
 *     @OA\Property(property="solde", type="number", format="float", readOnly="true", example=1000.00),
 *     @OA\Property(property="devise", type="string", readOnly="true", example="XOF"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", readOnly="true", example="2025-10-26T12:00:00Z"),
 *     @OA\Property(property="statut", type="string", readOnly="true", example="actif"),
 *     @OA\Property(property="metadata", type="object", readOnly="true", example={}),
 *     @OA\Property(property="client_id", type="integer", readOnly="true", example=1),
 *     @OA\Property(property="client_name", type="string", readOnly="true", example="Nom Prénom Client")
 * )
 */
class CompteResource extends JsonResource
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
            'numeroCompte' => $this->numeroCompte,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->dateCreation->toIso8601String(),
            'statut' => $this->statut,
            'metadata' => $this->metadata,
            'client_id' => $this->client_id,
            'client_name' => $this->whenLoaded('client', function () {
                return $this->client->nom . ' ' . $this->client->prenom;
            }),
            'archived' => (bool) $this->archived,
        ];
    }
}
