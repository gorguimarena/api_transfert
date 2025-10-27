<?php

namespace App\Http\Controllers;

use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\UserResource;
use App\Messages;
use App\Models\User;
use App\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

class AuthController extends Controller
{
    use ResponseTrait;

    /**
     * Authentifier un utilisateur
     *
     * Authentification d'un utilisateur et génération des tokens d'accès
     *
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
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
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse(Messages::IDENTIFIANTS_INVALIDES->value, 401);
        }

        // Utiliser les credentials du client password grant créé
        $client = (object) [
            'id' =>  env('PASSPORT_PASSWORD_CLIENT_ID'),
            'secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET')
        ];

        // Générer les tokens via Passport
        try {
            $tokenRequest = $request->create('/oauth/token', 'POST', [
                'grant_type' => 'password',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '*'
            ]);

            $tokenResponse = app()->handle($tokenRequest);
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if ($tokenResponse->getStatusCode() !== 200) {
                return $this->errorResponse(Messages::ERREUR_GENERATION_TOKEN->value . ': ' . ($tokenData['message'] ?? 'Erreur inconnue'), 500);
            }
        } catch (\Exception $e) {
            return $this->errorResponse(Messages::ERREUR_GENERATION_TOKEN->value . ': ' . $e->getMessage(), 500);
        }

        // Stocker le refresh token dans un cookie sécurisé
        $cookie = Cookie::make(
            'refresh_token',
            $tokenData['refresh_token'],
            60 * 24 * 30, // 30 jours
            null,
            null,
            true, // secure
            true  // httpOnly
        );

        return $this->successResponse(
            new LoginResource($user, $tokenData),
            Messages::CONNEXION_REUSSIE->value
        )->withCookie($cookie);
    }

    /**
     * Rafraîchir le token d'accès
     *
     * Utilise le refresh token pour générer un nouveau token d'accès
     *
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     tags={"Authentification"},
     *     summary="Rafraîchir le token d'accès",
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
    public function refresh(Request $request)
    {
        $refreshToken = $request->cookie('refresh_token');

        if (!$refreshToken) {
            return $this->errorResponse(Messages::REFRESH_TOKEN_MANQUANT->value, 401);
        }

        // Utiliser les credentials du client password grant créé
        $clientId = env('PASSPORT_PASSWORD_CLIENT_ID');
        $clientSecret = env('PASSPORT_PASSWORD_CLIENT_SECRET');

        if (!$clientId || !$clientSecret) {
            return $this->errorResponse('Configuration OAuth manquante', 500);
        }

        // Créer un objet client avec les infos de l'env
        $client = (object) [
            'id' => $clientId,
            'secret' => $clientSecret
        ];

        if (!$client) {
            return $this->errorResponse(Messages::CONFIGURATION_OAUTH_INVALIDE->value, 500);
        }

        // Rafraîchir le token via Passport
        $tokenRequest = $request->create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'refresh_token' => $refreshToken
        ]);

        $tokenResponse = app()->handle($tokenRequest);
        $tokenData = json_decode($tokenResponse->getContent(), true);

        if ($tokenResponse->getStatusCode() !== 200) {
            return $this->errorResponse(Messages::REFRESH_TOKEN_INVALIDE->value, 401);
        }

        // Mettre à jour le cookie avec le nouveau refresh token
        $cookie = Cookie::make(
            'refresh_token',
            $tokenData['refresh_token'],
            60 * 24 * 30, // 30 jours
            null,
            null,
            true, // secure
            true  // httpOnly
        );

        return $this->successResponse([
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in']
        ], Messages::TOKEN_RENOUVELE->value)->withCookie($cookie);
    }

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
    public function logout(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse(Messages::UTILISATEUR_NON_AUTHENTIFIE->value, 401);
        }

        // Révoquer tous les tokens de l'utilisateur via Passport
        \Laravel\Passport\Token::where('user_id', $user->id)->delete();

        // Supprimer le cookie refresh token
        $cookie = Cookie::forget('refresh_token');

        return $this->successResponse(null, Messages::DECONNEXION_REUSSIE->value)->withCookie($cookie);
    }

    /**
     * Récupérer les informations de l'utilisateur connecté
     *
     * Récupère les informations de l'utilisateur actuellement connecté
     *
     * @OA\Get(
     *     path="/api/v1/auth/user",
     *     tags={"Authentification"},
     *     summary="Informations utilisateur connecté",
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
    public function user(Request $request)
    {
        $user = auth('api')->user();

        if (!$user) {
            return $this->errorResponse(Messages::UTILISATEUR_NON_AUTHENTIFIE->value, 401);
        }

        return $this->successResponse(new UserResource($user));
    }
}
