<?php

namespace App\Interfaces;

use App\Http\Requests\AuthRequest;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    /**
     * Vérifie si le client OAuth existe, sinon le crée.
     */
    public function client_exist(): array;

    /**
     * Crée un client OAuth à partir des données du request/env.
     */
    public function create_client(string $client_id, string $client_scret): \Laravel\Passport\Client;

    /**
     * Génère un token via Passport (grant_type password).
     * Retourne un tableau de données ou false.
     */
    public function get_token(Request $request): array|bool;

    /**
     * Rafraîchit un token via Passport (grant_type refresh_token).
     * Retourne un tableau ou false.
     */
    public function refresh_token(Request $request): array|bool;

    /**
     * Révoque tous les tokens d’un utilisateur.
     */
    public function revoke_token(): bool;

    /**
     * Crée un cookie sécurisé pour le refresh_token.
     */
    public function set_cookie(string $refreshToken);

    /**
     * Supprime le cookie du refresh_token.
     */
    public function forget_cookie();

    /**
     * Retourne l’utilisateur actuellement authentifié.
     */
    // public function current_user(): ?\App\Models\User;

    public function login(AuthRequest $request): array|bool;
}
