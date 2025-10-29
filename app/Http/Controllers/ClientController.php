<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Resources\ClientResource;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *      path="/api/v1/clients/telephone/{telephone}",
     *      operationId="getClientByTelephone",
     *      tags={"Clients"},
     *      summary="Obtenir un client par téléphone",
     *      description="Retourne un client par son numéro de téléphone.",
     *      security={{"bearerAuth": {}}},
     *      @OA\Parameter(
     *          name="telephone",
     *          in="path",
     *          required=true,
     *          description="Numéro de téléphone du client",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Opération réussie",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Client récupéré avec succès"),
     *              @OA\Property(property="data", ref="#/components/schemas/ClientResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Client non trouvé",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Client non trouvé.")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Non authentifié",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthenticated.")
     *          )
     *      )
     * )
     */
    public function showByTelephone(string $telephone)
    {
        try {
            $client = Client::where('telephone', $telephone)->first();

            if (!$client) {
                return $this->error('Client non trouvé.', 404, 'CLIENT_NOT_FOUND');
            }

            return $this->success(new ClientResource($client), 'Client récupéré avec succès');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du client par téléphone: ' . $e->getMessage());
            return $this->error('Erreur interne du serveur lors de la récupération du client.', 500, 'INTERNAL_SERVER_ERROR', ['exception' => $e->getMessage()]);
        }
    }
}
