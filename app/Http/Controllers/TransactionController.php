<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Compte;
use App\Http\Resources\TransactionResource;
use App\ResponseTrait;
use App\Messages;
use App\TypeTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="compteId", type="string", format="uuid", description="ID du compte associé"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert"}, example="depot"),
 *     @OA\Property(property="montant", type="number", format="float", example=50000),
 *     @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
 *     @OA\Property(property="description", type="string", example="Dépôt initial", nullable=true),
 *     @OA\Property(property="dateTransaction", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"validee", "annulee"}, example="validee")
 * )
 */
class TransactionController extends Controller
{
    use ResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/transactions",
     *     tags={"Transactions"},
     *     summary="Liste paginée de toutes les transactions (admin)",
     *     description="Récupère une liste paginée de toutes les transactions avec filtres optionnels",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type de transaction",
     *         required=false,
     *         @OA\Schema(type="string", enum={"depot", "retrait", "transfert"})
     *     ),
     *     @OA\Parameter(
     *         name="date_debut",
     *         in="query",
     *         description="Date de début (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_fin",
     *         in="query",
     *         description="Date de fin (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Réservé aux administrateurs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès refusé")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = auth('api')->user();

        // Seuls les admins peuvent voir toutes les transactions
        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé - Réservé aux administrateurs', 403);
        }

        $query = Transaction::query();

        // Appliquer les filtres
        if ($request->has('type') && $request->type) {
            $query->where('type_transaction', $request->type);
        }

        if ($request->has('date_debut') && $request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->has('date_fin') && $request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);
        $transactions = $query->paginate($limit, ['*'], 'page', $page);

        $data = [
            'data' => TransactionResource::collection($transactions->items()),
            'pagination' => [
                'currentPage' => $transactions->currentPage(),
                'totalPages' => $transactions->lastPage(),
                'totalItems' => $transactions->total(),
                'itemsPerPage' => $transactions->perPage(),
                'hasNext' => $transactions->hasMorePages(),
                'hasPrevious' => $transactions->currentPage() > 1,
            ],
            'links' => [
                'self' => $request->url() . '?' . http_build_query($request->query()),
                'next' => $transactions->nextPageUrl(),
                'first' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => 1])),
                'last' => $request->url() . '?' . http_build_query(array_merge($request->query(), ['page' => $transactions->lastPage()])),
            ]
        ];

        return $this->successResponse($data, 'Transactions récupérées avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/{id}",
     *     tags={"Transactions"},
     *     summary="Détails d'une transaction",
     *     description="Récupère les détails d'une transaction spécifique",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la transaction récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transaction récupérée avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Transaction non trouvée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Transaction non trouvée")
     *         )
     *     )
     * )
     */
    public function show(Request $request, string $transactionId)
    {
        $user = auth('api')->user();

        try {
            $transaction = Transaction::findOrFail($transactionId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Transaction non trouvée', 404);
        }

        // Si c'est un client, vérifier que la transaction lui appartient
        if ($user->type === 'client') {
            if ($transaction->compte->client->user_id !== $user->id) {
                return $this->errorResponse('Accès non autorisé à cette transaction', 403);
            }
        }

        $transaction->load('compte.client.user');

        return $this->successResponse(new TransactionResource($transaction), 'Transaction récupérée avec succès');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/transactions",
     *     tags={"Transactions"},
     *     summary="Créer une nouvelle transaction",
     *     description="Crée une nouvelle transaction (dépôt, retrait, ou transfert)",
     *     security={{"token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "montant", "compte_id"},
     *             @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert"}, example="depot"),
     *             @OA\Property(property="montant", type="number", format="float", example=50000, minimum=100),
     *             @OA\Property(property="compte_id", type="string", format="uuid", description="ID du compte source"),
     *             @OA\Property(property="compte_destination_id", type="string", format="uuid", description="ID du compte destination (requis pour transfert)", nullable=true),
     *             @OA\Property(property="description", type="string", example="Dépôt initial", maxLength=255, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
         description="Transaction créée avec succès",
         *         @OA\JsonContent(
         *             @OA\Property(property="success", type="boolean", example=true),
         *             @OA\Property(property="message", type="string", example="Transaction créée avec succès"),
         *             @OA\Property(property="data", ref="#/components/schemas/Transaction")
         *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:depot,retrait,transfert',
            'montant' => 'required|numeric|min:100',
            'compte_id' => 'required|uuid|exists:comptes,id',
            'compte_destination_id' => 'nullable|uuid|exists:comptes,id|different:compte_id|required_if:type,transfert',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Erreur de validation', 422, $validator->errors());
        }

        $user = auth('api')->user();

        try {
            DB::beginTransaction();

            // Vérifier que le compte source existe et appartient à l'utilisateur (ou admin)
            $compteSource = Compte::findOrFail($request->compte_id);
            if ($user->type === 'client' && $compteSource->client->user_id !== $user->id) {
                return $this->errorResponse('Accès non autorisé à ce compte', 403);
            }

            // Pour les retraits et transferts, vérifier le solde
            if (in_array($request->type, ['retrait', 'transfert'])) {
                $soldeActuel = $compteSource->transactions()->sum('montant');
                if ($soldeActuel < $request->montant) {
                    return $this->errorResponse('Solde insuffisant', 422);
                }
            }

            // Pour les transferts, vérifier le compte destination
            if ($request->type === 'transfert') {
                if (!$request->has('compte_destination_id')) {
                    return $this->errorResponse('Le compte destination est requis pour un transfert', 422);
                }

                $compteDestination = Compte::findOrFail($request->compte_destination_id);
                if ($compteDestination->status_compte !== 'active') {
                    return $this->errorResponse('Le compte destination n\'est pas actif', 422);
                }
            }

            // Créer la transaction principale
            $transaction = Transaction::create([
                'montant' => $request->montant,
                'type_transaction' => TypeTransaction::from($request->type),
                'compte_id' => $request->compte_id,
                'compte_destination_id' => $request->compte_destination_id,
            ]);

            // Pour les transferts, créer la transaction de crédit sur le compte destination
            if ($request->type === 'transfert') {
                Transaction::create([
                    'montant' => $request->montant,
                    'type_transaction' => TypeTransaction::DEPOT,
                    'compte_id' => $request->compte_destination_id,
                    'compte_destination_id' => null, // Pas de destination pour un dépôt
                ]);
            }

            DB::commit();

            $transaction->load('compte.client.user');

            return $this->successResponse(new TransactionResource($transaction), 'Transaction créée avec succès', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Erreur lors de la création de la transaction: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{id}/transactions",
     *     tags={"Transactions"},
     *     summary="Liste des transactions d'un compte",
     *     description="Récupère la liste des transactions d'un compte spécifique",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transactions du compte récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Transactions récupérées avec succès"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function getByCompte(Request $request, string $compteId)
    {
        $user = auth('api')->user();

        try {
            $compte = Compte::findOrFail($compteId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Vérifier les permissions
        if ($user->type === 'client' && $compte->client->user_id !== $user->id) {
            return $this->errorResponse('Accès non autorisé à ce compte', 403);
        }

        $limit = $request->get('limit', 10);
        $page = $request->get('page', 1);

        $transactions = Transaction::where('compte_id', $compteId)
            ->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $data = [
            'data' => TransactionResource::collection($transactions->items()),
            'pagination' => [
                'currentPage' => $transactions->currentPage(),
                'totalPages' => $transactions->lastPage(),
                'totalItems' => $transactions->total(),
                'itemsPerPage' => $transactions->perPage(),
                'hasNext' => $transactions->hasMorePages(),
                'hasPrevious' => $transactions->currentPage() > 1,
            ]
        ];

        return $this->successResponse($data, 'Transactions du compte récupérées avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{id}/transactions/stats",
     *     tags={"Transactions"},
     *     summary="Statistiques du compte",
     *     description="Récupère les statistiques d'un compte (solde, nombre de transactions, etc.)",
     *     security={{"token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistiques récupérées avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="solde", type="number", example=1250000),
     *                 @OA\Property(property="totalDepots", type="number", example=1500000),
                 @OA\Property(property="totalRetraits", type="number", example=250000),
                 @OA\Property(property="nombreTransactions", type="integer", example=45),
                 @OA\Property(property="derniereTransaction", type="string", format="date-time", nullable=true)
             )
         )
     )
     * )
     */
    public function getStats(Request $request, string $compteId)
    {
        $user = auth('api')->user();

        try {
            $compte = Compte::findOrFail($compteId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Compte non trouvé', 404);
        }

        // Vérifier les permissions
        if ($user->type === 'client' && $compte->client->user_id !== $user->id) {
            return $this->errorResponse('Accès non autorisé à ce compte', 403);
        }

        $stats = [
            'solde' => $compte->transactions()->sum('montant'),
            'totalDepots' => Transaction::where('compte_id', $compteId)
                ->where('type_transaction', TypeTransaction::DEPOT)
                ->sum('montant'),
            'totalRetraits' => Transaction::where('compte_id', $compteId)
                ->where('type_transaction', TypeTransaction::RETRAIT)
                ->sum('montant'),
            'nombreTransactions' => Transaction::where('compte_id', $compteId)->count(),
            'derniereTransaction' => Transaction::where('compte_id', $compteId)
                ->latest('created_at')
                ->value('created_at'),
        ];

        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/recentes",
     *     tags={"Transactions"},
     *     summary="10 dernières transactions globales",
     *     description="Récupère les 10 dernières transactions de tous les comptes (admin seulement)",
     *     security={{"token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dernières transactions récupérées avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dernières transactions récupérées avec succès"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Transaction"))
         )
     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé - Réservé aux administrateurs",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès refusé")
         )
     )
     * )
     */
    public function getRecentes(Request $request)
    {
        $user = auth('api')->user();

        // Seuls les admins peuvent voir les transactions récentes globales
        if ($user->type !== 'admin') {
            return $this->errorResponse('Accès refusé - Réservé aux administrateurs', 403);
        }

        $transactions = Transaction::with('compte.client.user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $this->successResponse(TransactionResource::collection($transactions), 'Dernières transactions récupérées avec succès');
    }
}
