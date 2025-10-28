<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="TransactionResource",
 *     description="Transaction resource model",
 *     @OA\Xml(
 *         name="TransactionResource"
 *     )
 * )
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            /**
             * @OA\Property(property="id", type="string", format="uuid", description="ID de la transaction")
             */
            'id' => $this->id,
            /**
             * @OA\Property(property="reference", type="string", description="Référence unique de la transaction")
             */
            'reference' => $this->reference,
            /**
             * @OA\Property(property="type", type="string", enum={"credit", "debit"}, description="Type de transaction (crédit ou débit)")
             */
            'type' => $this->type,
            /**
             * @OA\Property(property="montant", type="number", format="float", description="Montant de la transaction")
             */
            'montant' => $this->montant,
            /**
             * @OA\Property(property="devise", type="string", description="Devise de la transaction")
             */
            'devise' => $this->devise,
            /**
             * @OA\Property(property="description", type="string", nullable=true, description="Description de la transaction")
             */
            'description' => $this->description,
            /**
             * @OA\Property(property="dateTransaction", type="string", format="date-time", description="Date et heure de la transaction")
             */
            'dateTransaction' => $this->dateTransaction?->toISOString(),
            /**
             * @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}, description="Statut de la transaction")
             */
            'status' => $this->status,
            /**
             * @OA\Property(property="archived", type="boolean", description="Indique si la transaction est archivée")
             */
            'archived' => $this->archived,
            /**
             * @OA\Property(property="compte_id", type="string", format="uuid", description="ID du compte associé à la transaction")
             */
            'compte_id' => $this->compte_id,
            /**
             * @OA\Property(property="created_at", type="string", format="date-time", description="Date de création de la transaction")
             */
            'created_at' => $this->created_at?->toISOString(),
            /**
             * @OA\Property(property="updated_at", type="string", format="date-time", description="Date de dernière mise à jour de la transaction")
             */
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
