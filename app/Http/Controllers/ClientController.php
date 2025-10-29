<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use App\Http\Resources\ClientResource;
use App\ResponseTrait;
use Illuminate\Http\Request;

/**
 * @OA\Schema(
 *     schema="ClientDetails",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="nom", type="string", example="Diallo"),
 *     @OA\Property(property="prenom", type="string", example="Amadou"),
 *     @OA\Property(property="nci", type="string", example="1234567890123"),
 *     @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ClientController extends Controller
{
    use ResponseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Récupérer un client à partir de son numéro de téléphone
     *
     * Récupère les informations d'un client en utilisant son numéro de téléphone
     *
     * @OA\Get(
     *     path="/api/v1/clients/telephone/{telephone}",
     *     tags={"Clients"},
     *     summary="Récupérer un client par numéro de téléphone",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="telephone",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", example="+221771234567")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client trouvé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientDetails")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client non trouvé")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function getByTelephone(Request $request, string $telephone)
    {
        $user = auth('api')->user();

        // Recherche du compte avec ce numéro de téléphone
        $query = Client::with('user')
            ->whereHas('comptes', function ($query) use ($telephone) {
                $query->where('telephone', $telephone);
            });

        // Si c'est un client, vérifier que le numéro lui appartient
        if ($user->type === 'client') {
            $query->where('user_id', $user->id);
        }
        // Si c'est un admin, pas de restriction supplémentaire

        $client = $query->first();

        if (!$client) {
            $message = $user->type === 'client'
                ? 'Client non trouvé ou numéro de téléphone ne vous appartient pas'
                : 'Client non trouvé pour ce numéro de téléphone';
            return $this->errorResponse($message, 404);
        }

        return $this->successResponse(new ClientResource($client), 'Client trouvé avec succès');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }
}
