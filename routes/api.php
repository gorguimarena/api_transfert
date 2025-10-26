<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Middleware\LoggingMiddleware;

Route::middleware(['api', LoggingMiddleware::class])->group(function () {

    Route::prefix('v1')->group(function () {
        // Routes d'authentification (sans middleware auth)
        Route::prefix('auth')->group(function () {
            /**
             * @OA\Post(
             *     path="/api/v1/auth/login",
             *     tags={"Authentification"},
             *     summary="Connexion utilisateur",
             *     description="Authentification d'un utilisateur et génération des tokens d'accès",
             *     @OA\RequestBody(
             *         required=true,
             *         @OA\JsonContent(
             *             required={"email", "password"},
             *             @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
             *             @OA\Property(property="password", type="string", example="password"),
             *             @OA\Property(property="remember", type="boolean", example=true)
             *         )
             *     ),
             *     @OA\Response(
             *         response=200,
             *         description="Connexion réussie",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string", example="Connexion réussie"),
             *             @OA\Property(property="data", type="object",
             *                 @OA\Property(property="user", ref="#/components/schemas/User"),
             *                 @OA\Property(property="access_token", type="string"),
             *                 @OA\Property(property="token_type", type="string", example="Bearer"),
             *                 @OA\Property(property="expires_in", type="integer"),
             *                 @OA\Property(property="refresh_token", type="string")
             *             )
             *         )
             *     ),
             *     @OA\Response(
             *         response=401,
             *         description="Identifiants invalides",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=false),
             *             @OA\Property(property="message", type="string", example="Identifiants invalides")
             *         )
             *     )
             * )
             */
            Route::post('/login', [AuthController::class, 'login']);

            /**
             * @OA\Post(
             *     path="/api/v1/auth/refresh",
             *     tags={"Authentification"},
             *     summary="Rafraîchir le token d'accès",
             *     description="Utilise le refresh token pour générer un nouveau token d'accès",
             *     @OA\Response(
             *         response=200,
             *         description="Token rafraîchi",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
             *             @OA\Property(property="data", type="object",
             *                 @OA\Property(property="access_token", type="string"),
             *                 @OA\Property(property="token_type", type="string", example="Bearer"),
             *                 @OA\Property(property="expires_in", type="integer")
             *             )
             *         )
             *     ),
             *     @OA\Response(
             *         response=401,
             *         description="Refresh token invalide",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=false),
             *             @OA\Property(property="message", type="string", example="Refresh token invalide")
             *         )
             *     )
             * )
             */
            Route::post('/refresh', [AuthController::class, 'refresh']);

            /**
             * @OA\Post(
             *     path="/api/v1/auth/logout",
             *     tags={"Authentification"},
             *     summary="Déconnexion utilisateur",
             *     description="Invalide le token d'accès actuel et supprime le refresh token",
             *     security={{"passport":{}}},
             *     @OA\Response(
             *         response=200,
             *         description="Déconnexion réussie",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
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
            Route::middleware('auth:api')->post('/logout', [AuthController::class, 'logout']);

            /**
             * @OA\Get(
             *     path="/api/v1/auth/user",
             *     tags={"Authentification"},
             *     summary="Informations utilisateur connecté",
             *     description="Récupère les informations de l'utilisateur actuellement connecté",
             *     security={{"passport":{}}},
             *     @OA\Response(
             *         response=200,
             *         description="Informations utilisateur",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="data", ref="#/components/schemas/User")
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
            Route::middleware('auth:api')->get('/user', [AuthController::class, 'user']);
        });

        Route::prefix('comptes')->group(function () {
            /**
             * @OA\Get(
             *     path="/api/v1/comptes",
             *     tags={"Comptes"},
             *     summary="Lister tous les comptes",
             *     description="Récupère une liste paginée des comptes bancaires actifs",
             *     @OA\Response(
             *         response=200,
             *         description="Opération réussie",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=true),
             *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
             *             @OA\Property(property="data", type="object")
             *         )
             *     )
             * )
             */
            Route::get('/', [CompteController::class, 'index']);

            /**
             * @OA\Post(
             *     path="/api/v1/comptes",
             *     tags={"Comptes"},
             *     summary="Créer un nouveau compte",
             *     description="Créer un nouveau compte bancaire avec génération automatique de numéro de compte",
             *     @OA\RequestBody(
             *         required=true,
             *         @OA\JsonContent(
             *             required={"type_compte", "soldeInitial", "telephone", "client"},
             *             @OA\Property(property="type_compte", type="string", enum={"epargne", "cheque"}, example="epargne"),
             *             @OA\Property(property="devise", type="string", enum={"FCFA", "EUR", "USD"}, example="FCFA"),
             *             @OA\Property(property="soldeInitial", type="number", example=500000),
             *             @OA\Property(property="telephone", type="string", example="+22182151079"),
             *             @OA\Property(property="client", type="object",
             *                 required={"nom", "prenom", "email"},
             *                 @OA\Property(property="nom", type="string", example="Bashirian"),
             *                 @OA\Property(property="prenom", type="string", example="Mylene"),
             *                 @OA\Property(property="email", type="string", format="email", example="gennaro27@example.org"),
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
             *             @OA\Property(property="data", type="object")
             *         )
             *     ),
             *     @OA\Response(
             *         response=400,
             *         description="Erreur de validation",
             *         @OA\JsonContent(
             *             @OA\Property(property="success", type="boolean", example=false),
             *             @OA\Property(property="error", type="object",
             *                 @OA\Property(property="code", type="string", example="VALIDATION_ERROR"),
             *                 @OA\Property(property="message", type="string", example="Les données fournies sont invalides"),
             *                 @OA\Property(property="details", type="object")
             *             )
             *         )
             *     )
             * )
             */
            Route::post('/', [CompteController::class, 'store']);
        });
    });
});

