<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="OAuth2",
 *     description="Routes OAuth2 générées automatiquement par Laravel Passport"
 * )
 */
class PassportDocController extends Controller
{
    /**
     * @OA\Post(
     *     path="/oauth/token",
     *     tags={"OAuth2"},
     *     summary="Obtenir un token d'accès",
     *     description="Authentification OAuth2 pour obtenir un access token. Route générée par Passport.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type", "client_id", "client_secret", "username", "password"},
     *             @OA\Property(property="grant_type", type="string", enum={"password"}, example="password"),
     *             @OA\Property(property="client_id", type="string", example="1"),
     *             @OA\Property(property="client_secret", type="string", example="client-secret-here"),
     *             @OA\Property(property="username", type="string", example="admin@example.com"),
     *             @OA\Property(property="password", type="string", example="password"),
     *             @OA\Property(property="scope", type="string", example="*")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token obtenu avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erreur d'authentification",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="invalid_credentials"),
     *             @OA\Property(property="message", type="string", example="The user credentials were incorrect.")
     *         )
     *     )
     * )
     */
    public function oauthToken(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Route OAuth2 générée par Passport']);
    }

    /**
     * @OA\Post(
     *     path="/oauth/token/refresh",
     *     tags={"OAuth2"},
     *     summary="Rafraîchir un token d'accès",
     *     description="Utiliser un refresh token pour obtenir un nouveau access token. Route générée par Passport.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grant_type", "refresh_token", "client_id", "client_secret"},
     *             @OA\Property(property="grant_type", type="string", enum={"refresh_token"}, example="refresh_token"),
     *             @OA\Property(property="refresh_token", type="string", example="refresh-token-here"),
     *             @OA\Property(property="client_id", type="string", example="1"),
     *             @OA\Property(property="client_secret", type="string", example="client-secret-here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Nouveau token obtenu",
     *         @OA\JsonContent(
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=31536000),
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     )
     * )
     */
    public function oauthRefresh(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Get(
     *     path="/oauth/clients",
     *     tags={"OAuth2"},
     *     summary="Lister les clients OAuth2",
     *     description="Récupérer la liste des clients OAuth2 enregistrés. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="redirect", type="string"),
     *             @OA\Property(property="personal_access_client", type="boolean"),
     *             @OA\Property(property="password_client", type="boolean"),
     *             @OA\Property(property="revoked", type="boolean")
     *         ))
     *     )
     * )
     */
    public function oauthClients(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Post(
     *     path="/oauth/clients",
     *     tags={"OAuth2"},
     *     summary="Créer un client OAuth2",
     *     description="Créer un nouveau client OAuth2. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "redirect"},
     *             @OA\Property(property="name", type="string", example="My App"),
     *             @OA\Property(property="redirect", type="string", example="http://localhost/callback")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="secret", type="string"),
     *             @OA\Property(property="redirect", type="string")
     *         )
     *     )
     * )
     */
    public function createOauthClient(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Put(
     *     path="/oauth/clients/{client_id}",
     *     tags={"OAuth2"},
     *     summary="Modifier un client OAuth2",
     *     description="Modifier les informations d'un client OAuth2. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="client_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "redirect"},
     *             @OA\Property(property="name", type="string", example="Updated App Name"),
     *             @OA\Property(property="redirect", type="string", example="http://localhost/new-callback")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Client modifié")
     * )
     */
    public function updateOauthClient(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Delete(
     *     path="/oauth/clients/{client_id}",
     *     tags={"OAuth2"},
     *     summary="Supprimer un client OAuth2",
     *     description="Supprimer un client OAuth2. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\Parameter(
     *         name="client_id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Client supprimé")
     * )
     */
    public function deleteOauthClient(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Get(
     *     path="/oauth/scopes",
     *     tags={"OAuth2"},
     *     summary="Lister les scopes OAuth2",
     *     description="Récupérer la liste des scopes OAuth2 disponibles. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des scopes",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="string"),
     *             @OA\Property(property="description", type="string")
     *         ))
     *     )
     * )
     */
    public function oauthScopes(Request $request): JsonResponse
    {
        return response()->json([]);
    }

    /**
     * @OA\Get(
     *     path="/oauth/user",
     *     tags={"OAuth2"},
     *     summary="Informations utilisateur OAuth2",
     *     description="Récupérer les informations de l'utilisateur OAuth2. Route générée par Passport.",
     *     security={{"passport":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Informations utilisateur",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", format="uuid"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="type", type="string", enum={"admin", "client"})
     *         )
     *     )
     * )
     */
    public function oauthUser(Request $request): JsonResponse
    {
        return response()->json([]);
    }
}