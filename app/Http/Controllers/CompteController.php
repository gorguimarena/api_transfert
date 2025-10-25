<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Helpers\QueryHelper;
use App\Http\Requests\StoreCompteRequest;
use App\Messages;
use App\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="API de Transfert Bancaire",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 * @OA\Server(
 *     url="http://localhost:8080/api",
 *     description="Serveur de développement"
 * )
 * @OA\Server(
 *     url="https://api-transfert.onrender.com/api",
 *     description="Serveur de production"
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
 *     @OA\Property(property="numero_compte", type="string", example="123456789"),
 *     @OA\Property(property="type_compte", type="string", enum={"epargne", "cheque"}, example="epargne"),
 *     @OA\Property(property="status_compte", type="string", enum={"active", "bloque"}, example="active"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="client", ref="#/components/schemas/Client"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="user", ref="#/components/schemas/User"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="type", type="string", enum={"client", "admin"}, example="client"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */

class CompteController extends Controller
{
    use ResponseTrait;

    // Configuration des filtres
    protected $filters = [
            [
                'field' => 'numero_compte',
                'operator' => 'like',
                'type' => 'like',
                'request_key' => 'numero_compte'
            ],
            [
                'field' => 'name',
                'relation' => 'client.user',
                'operator' => 'like',
                'type' => 'like',
                'request_key' => 'nom_user'
            ],
            [
                'field' => 'type_compte',
                'request_key' => 'type'
            ],
            [
                'field' => 'status_compte',
                'request_key' => 'statut'
            ]
        ];

        // Mapping des champs de tri
        protected $sortMapping = [
            'dateCreation' => 'created_at',
            'numero_compte' => 'numero_compte',
            'type_compte' => 'type_compte',
            'status_compte' => 'status_compte',
        ];
    /**
     * @OA\Get(
     *     path="/api/V1/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes avec filtres optionnels",
     *     description="Récupère une liste paginée des comptes bancaires avec filtrage optionnel par numéro de compte, nom d'utilisateur, type et statut",
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="query",
     *         description="Filtrer par numéro de compte (correspondance partielle)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="nom_user",
     *         in="query",
     *         description="Filtrer par nom d'utilisateur (correspondance partielle)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"epargne", "cheque"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut du compte",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"active", "bloque"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Champ de tri",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"dateCreation", "numero_compte", "type_compte", "status_compte"},
     *             default="dateCreation"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre de tri",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"asc", "desc"},
     *             default="desc"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Compte::with('client.user');
        
        $query = QueryHelper::applyFilters($query, $request, $this->filters);
        $query = QueryHelper::applySorting($query, $request, $this->sortMapping);

        $limit = $request->get('limit', 10);
        $comptes = $query->paginate($limit);

        return $this->successResponse($comptes, Messages::COMPTES_RECUPERES->value);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     description="Créer un nouveau compte bancaire. Si user_id est fourni, utilise le client existant. Sinon, crée un nouveau client avec les informations fournies.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"numero_compte", "type_compte", "telephone"},
     *             @OA\Property(property="numero_compte", type="string", example="123456789"),
     *             @OA\Property(property="type_compte", type="string", enum={"epargne", "cheque"}, example="epargne"),
     *             @OA\Property(property="status_compte", type="string", enum={"active", "bloque"}, example="active"),
     *             @OA\Property(property="telephone", type="string", example="+221771234567"),
     *             @OA\Property(property="user_id", type="string", format="uuid", description="ID utilisateur existant (optionnel)"),
     *             @OA\Property(property="client_name", type="string", description="Nom du client (requis si pas de user_id)", example="John Doe"),
     *             @OA\Property(property="client_email", type="string", format="email", description="Email du client (requis si pas de user_id)", example="john@example.com"),
     *             @OA\Property(property="client_password", type="string", description="Mot de passe du client (requis si pas de user_id)", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(StoreCompteRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            $client = $user->client;

            $compte = Compte::create([
                'numero_compte' => $request->numero_compte,
                'type_compte' => $request->type_compte,
                'status_compte' => $request->status_compte ?? 'active',
                'telephone' => $request->telephone,
                'client_id' => $client->id,
            ]);

            DB::commit();

            $compte->load('client.user');

            return $this->successResponse($compte, Messages::COMPTE_CREE->value, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse(Messages::ERREUR_CREATION_COMPTE->value . ': ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Compte $compte)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Compte $compte)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Compte $compte)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Compte $compte)
    {
        //
    }
}
