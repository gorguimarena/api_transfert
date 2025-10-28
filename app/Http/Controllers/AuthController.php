<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Interfaces\AuthServiceInterface;
use App\Messages;
use App\Models\User;
use App\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ResponseTrait;

    public function __construct(private AuthServiceInterface $authService) {}

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     tags={"Authentification"},
     *     summary="Connexion utilisateur",
     *     description="Authentification d'un utilisateur et génération des tokens d'accès OAuth2",
     *     security={{"passport":{}}},
     *     operationId="loginUser",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informations de connexion",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@example.com", description="Adresse email de l'utilisateur"),
     *             @OA\Property(property="password", type="string", example="password", description="Mot de passe de l'utilisateur"),
     *             @OA\Property(property="remember", type="boolean", example=true, description="Se souvenir de la connexion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", description="Token d'accès JWT (expire en 1 heure)"),
     *                 @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes (3600)"),
     *                 @OA\Property(property="refresh_token", type="string", description="Token de rafraîchissement (expire en 30 jours, stocké en cookie sécurisé)")
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
    public function login(AuthRequest $request)
    {
        $result = $this->authService->login($request);

        if (!$result['status']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        return $this->successResponse(
            $result['data'],
            $result['message']
        )->withCookie($result['cookie'])
            ->header('Access-Control-Allow-Credentials', 'true');
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
     *     security={{"token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", description="Nouveau token d'accès JWT"),
     *                 @OA\Property(property="expires_in", type="integer", description="Durée de validité en secondes"),
     *                 @OA\Property(property="refresh_token", type="string", description="Nouveau token de rafraîchissement")
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

        $client = $this->authService->client_exist();

        if (!$client || !isset($client['id'], $client['secret'])) {
            return $this->errorResponse(Messages::CONFIGURATION_OAUTH_INVALIDE->value, 500);
        }

        $tokenData = $this->authService->refresh_token($request);

        if (!$tokenData || !isset($tokenData['access_token'])) {
            return $this->errorResponse(Messages::REFRESH_TOKEN_INVALIDE->value, 401);
        }

        $cookie = $this->authService->set_cookie($tokenData['refresh_token']);

        return $this->successResponse([
            'access_token' => $tokenData['access_token'],
            'expires_in' => $tokenData['expires_in'] ?? null,
            'refresh_token' => $tokenData['refresh_token']
        ], Messages::TOKEN_RENOUVELE->value)
            ->withCookie($cookie)
            ->header('Access-Control-Allow-Credentials', 'true');
    }


    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     tags={"Authentification"},
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel et supprime le refresh token",
     *     security={{"token":{}}},
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

        $revoked = $this->authService->revoke_token();

        if (!$revoked) {
            return $this->errorResponse(Messages::ERREUR_REVOCATION_TOKEN->value, 500);
        }

        $cookie = $this->authService->forget_cookie();

        return $this->successResponse(
            null,
            Messages::DECONNEXION_REUSSIE->value
        )->withCookie($cookie)
            ->header('Access-Control-Allow-Credentials', 'true');
    }
}
