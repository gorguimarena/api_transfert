<?php

namespace App\Services;

use App\Http\Requests\AuthRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Interfaces\AuthServiceInterface;
use App\Messages;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Client;
use Laravel\Passport\Token;
use Illuminate\Http\Request;

class AuthServicePassport implements AuthServiceInterface
{
    /**
     * Vérifie si le client OAuth existe sinon le crée.
     */
    public function client_exist(): array
    {
        // Utiliser la configuration depuis config/services.php
        $clientId = config('services.passport.client_id');
        $clientSecret = config('services.passport.client_secret');

        // Vérifier que la configuration est définie
        if (!$clientId || !$clientSecret) {
            Log::error('Configuration Passport manquante dans config/services.php', [
                'clientId' => $clientId ? 'set' : 'missing',
                'clientSecret' => $clientSecret ? 'set' : 'missing'
            ]);
            throw new \Exception('Configuration Passport manquante');
        }

        // Vérifier que le client existe en base
        $client = Client::where('id', $clientId)->first();

        if (!$client) {
            Log::info("Client OAuth non trouvé en base, création en cours...");
            $client = $this->create_client($clientId, $clientSecret);
        }

        Log::info('Client OAuth configuré', [
            'id' => $clientId,
            'exists_in_db' => $client ? 'yes' : 'no',
            'has_secret' => $clientSecret ? 'yes' : 'no'
        ]);

        return [
            'id' => $clientId,
            'secret' => $clientSecret,
        ];
    }

    /**
     * Crée un client OAuth.
     */
    public function create_client(string $client_id, string $client_secret): Client
    {
        return Client::create([
            'id' => $client_id,
            'name' => 'Password Grant Client',
            'secret' => $client_secret,
            'provider' => 'users',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);
    }

    /**
     * Génère un token via Passport (password grant).
     */
    public function get_token(Request $request): array|bool
    {
        try {
            $client = $this->client_exist();

            $tokenRequest = $request->create('/oauth/token', 'POST', [
                'grant_type' => 'password',
                'client_id' => $client['id'],
                'client_secret' => $client['secret'],
                'username' => $request->email,
                'password' => $request->password,
                'scope' => '*'
            ]);

            $tokenResponse = app()->handle($tokenRequest);
            $tokenData = json_decode($tokenResponse->getContent(), true);

            if ($tokenResponse->getStatusCode() !== 200) {
                Log::error('Erreur génération token Passport', [
                    'status' => $tokenResponse->getStatusCode(),
                    'response' => $tokenData
                ]);
                return false;
            }

            return $tokenData;
        } catch (\Exception $e) {
            Log::error('Erreur interne Passport', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Rafraîchit un token.
     */
    public function refresh_token(Request $request): array|bool
    {
        $refreshToken = $request->cookie('refresh_token');
        if (!$refreshToken) return false;

        $client = $this->client_exist();

        $tokenRequest = $request->create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['id'],
            'client_secret' => $client['secret'],
            'refresh_token' => $refreshToken
        ]);

        $tokenResponse = app()->handle($tokenRequest);
        $tokenData = json_decode($tokenResponse->getContent(), true);

        return $tokenResponse->getStatusCode() === 200 ? $tokenData : false;
    }

    /**
     * Révoque tous les tokens utilisateur.
     */
    public function revoke_token(): bool
    {
        $user = auth('api')->user();
        if (!$user) return false;

        Token::where('user_id', $user->id)->delete();
        return true;
    }

    /**
     * Définit le cookie refresh_token.
     */
    public function set_cookie(string $refreshToken)
    {
        return Cookie::make(
            'refresh_token',
            $refreshToken,
            60 * 24 * 30,
            null,
            null,
            true,
            true
        );
    }

    /**
     * Supprime le cookie.
     */
    public function forget_cookie()
    {
        return Cookie::forget('refresh_token');
    }

    /**
     * Authentifie un utilisateur (login).
     */
    public function login(AuthRequest $request): array|bool
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'status' => false,
                'message' => Messages::IDENTIFIANTS_INVALIDES->value,
                'code' => 401
            ];
        }

        $tokenData = $this->get_token($request);
        if (!$tokenData) {
            return [
                'status' => false,
                'message' => Messages::ERREUR_GENERATION_TOKEN->value,
                'code' => 500
            ];
        }

        $cookie = $this->set_cookie($tokenData['refresh_token']);

        return [
            'status' => true,
            'data' => new LoginResource($user, $tokenData),
            'message' => Messages::CONNEXION_REUSSIE->value,
            'cookie' => $cookie
        ];
    }
}
