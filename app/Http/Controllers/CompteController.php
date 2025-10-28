<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Client;
use App\Models\User;
use App\Helpers\QueryHelper;
use App\Http\Requests\CreateCompteRequest;
use App\Http\Resources\CompteResource;
use App\Events\CompteCreated;
use App\Messages;
use App\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(
 *     title="API de Transfert Bancaire",
 *     version="1.0.0",
 *     description="API REST pour la gestion des comptes bancaires avec authentification OAuth2"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Entrer le token JWT au format : Bearer {votre_token}"
 * )
 * @OA\Server(
 *     url="http://localhost:9000",
 *     description="Serveur de développement"
 * )
 * @OA\Server(
 *     url="https://api-transfert.onrender.com",
 *     description="Serveur de production"
 * )
 *
 * @OA\Schema(
 *     schema="Compte",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
 *     @OA\Property(property="solde", type="number", format="float", example=1250000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"active", "bloque"}, example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", example="Inactivité de 30+ jours", nullable=true),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
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
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Lister tous les comptes avec filtres optionnels",
     *     description="Récupère une liste paginée des comptes bancaires avec filtrage optionnel par numéro de compte, nom d'utilisateur, type et statut",
     *     security={{"token":{}}},
     *     operationId="listComptes",
     *     @OA\Parameter(
     *         name="numero_compte",
     *         in="query",
     *         description="Filtrer par numéro de compte (correspondance partielle)",
     *         required=false,
     *         @OA\Schema(type="string", example="20251026")
     *     ),
     *     @OA\Parameter(
     *         name="nom_user",
     *         in="query",
     *         description="Filtrer par nom d'utilisateur (correspondance partielle)",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de compte",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"epargne", "cheque"},
     *             example="cheque"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut du compte",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"active", "bloque"},
     *             example="active"
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
     *             default="dateCreation",
     *             example="dateCreation"
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
     *             default="desc",
     *             example="desc"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100, example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1, example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Compte")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="currentPage", type="integer", example=1),
     *                     @OA\Property(property="totalPages", type="integer", example=3),
     *                     @OA\Property(property="totalItems", type="integer", example=25),
     *                     @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                     @OA\Property(property="hasNext", type="boolean", example=true),
     *                     @OA\Property(property="hasPrevious", type="boolean", example=false)
     *                 ),
     *                 @OA\Property(property="links", type="object",
     *                     @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                     @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
     *                     @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                     @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Utilisateur non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation des paramètres",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $query = Compte::with('client.user')->active()->forUser();

        $query = QueryHelper::applyFilters($query, $request, $this->filters);
        $query = QueryHelper::applySorting($query, $request, $this->sortMapping);

        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $comptes = $query->paginate($limit, ['*'], 'page', $page);

        $data = [
            'data' => CompteResource::collection($comptes->items()),
            'pagination' => [
                'currentPage' => $comptes->currentPage(),
                'totalPages' => $comptes->lastPage(),
                'totalItems' => $comptes->total(),
                'itemsPerPage' => $comptes->perPage(),
                'hasNext' => $comptes->hasMorePages(),
                'hasPrevious' => $comptes->currentPage() > 1,
            ],
            'links' => [
                'self' => $request->url() . '?' . http_build_query($request->query()),
                'next' => $comptes->nextPageUrl(),
                'first' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => 1])),
                'last' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $comptes->lastPage()])),
            ]
        ];

        $message = $user->type === 'client' ? 'Vos comptes récupérés avec succès' : Messages::COMPTES_RECUPERES->value;
        return $this->successResponse($data, $message)->header('Access-Control-Allow-Credentials', 'true');
    }

    /**
     * Créer un nouveau compte bancaire
     *
     * Créer un nouveau compte bancaire avec génération automatique de numéro de compte
     *
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     tags={"Comptes"},
     *     summary="Créer un nouveau compte",
     *     security={{"token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "soldeInitial", "client"},
     *             @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="cheque"),
     *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
     *             @OA\Property(property="soldeInitial", type="number", example=500000),
     *             @OA\Property(property="client", type="object",
     *                 required={"titulaire", "email", "telephone"},
     *                 @OA\Property(property="id", type="string", format="uuid", description="ID utilisateur existant (optionnel)", example=null),
     *                 @OA\Property(property="titulaire", type="string", example="Hawa BB Wane"),
     *                 @OA\Property(property="email", type="string", format="email", example="cheikh.sy@example.com"),
     *                 @OA\Property(property="telephone", type="string", example="+221771234567"),
     *                 @OA\Property(property="nci", type="string", example=""),
     *                 @OA\Property(property="adresse", type="string", example="Dakar, Sénégal")
     *             )
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
    public function store(CreateCompteRequest $request)
    {
        try {
            DB::beginTransaction();

            $client = null;
            $generatedPassword = null;
            $verificationCode = null;

            if ($request->has('client.id') && $request->client['id']) {
                // Utiliser le client existant
                $user = User::findOrFail($request->client['id']);
                $client = $user->client;
                if (!$client) {
                    throw new \Exception('Le client associé à cet utilisateur n\'existe pas.');
                }
            } else {
                
                $generatedPassword = $this->generatePassword();

                // Générer un code de vérification
                $verificationCode = $this->generateVerificationCode();

                // Parser le titulaire pour extraire nom et prénom
                $titulaireParts = explode(' ', $request->client['titulaire'], 2);
                $nom = $titulaireParts[0] ?? '';
                $prenom = $titulaireParts[1] ?? '';

                // Créer l'utilisateur
                $user = User::create([
                    'name' => $request->client['titulaire'],
                    'email' => $request->client['email'],
                    'password' => Hash::make($generatedPassword),
                    'type' => 'client',
                ]);

                // Créer le client
                $client = Client::create([
                    'user_id' => $user->id,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'nci' => $request->client['nci'] ?? null,
                    'adresse' => $request->client['adresse'] ?? null,
                    'code_verification' => $verificationCode,
                    'code_utilise' => false,
                ]);
            }

            // Créer le compte
            $compte = Compte::create([
                'numero_compte' => Compte::generateNumeroCompte(),
                'type_compte' => $request->type,
                'status_compte' => 'active',
                'telephone' => $request->client['telephone'],
                'devise' => $request->devise ?? 'FCFA',
                'solde_initial' => $request->soldeInitial,
                'is_deleted' => false,
                'client_id' => $client->id,
            ]);

            DB::commit();

            $compte->load('client.user');

            // Déclencher l'événement pour envoyer les notifications seulement si c'est un nouveau client
            if ($generatedPassword && $verificationCode) {
                event(new CompteCreated($compte, $generatedPassword, $verificationCode));
            }

            return $this->successResponse(new CompteResource($compte), Messages::COMPTE_CREE->value, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Erreur lors de la création du compte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Afficher les détails d'un compte spécifique
     *
     * Récupère les détails d'un compte spécifique
     *
     * @OA\Get(
     *     path="/api/v1/comptes/{id}",
     *     tags={"Comptes"},
     *     summary="Détails d'un compte",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Opération réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Compte non trouvé ou inactif")
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
    public function show(Request $request, Compte $compte)
    {
        // Vérifier que le compte est actif
        if (!$compte->active()->exists()) {
            return $this->errorResponse('Compte non trouvé ou inactif', 404);
        }

        $compte->load('client.user');

        return $this->successResponse(new CompteResource($compte), 'Compte récupéré avec succès')->header('Access-Control-Allow-Credentials', 'true');
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

    /**
     * Génère un mot de passe temporaire
     */
    private function generatePassword(): string
    {
        return 'Temp' . rand(100000, 999999) . '!';
    }

    /**
     * Génère un code de vérification à 6 chiffres
     */
    private function generateVerificationCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
