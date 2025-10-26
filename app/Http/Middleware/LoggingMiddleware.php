<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Informations de la requête
        $method = $request->method();
        $uri = $request->getRequestUri();
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $user = auth('api')->user();

        // Log de la requête entrante
        Log::info('API Request Started', [
            'method' => $method,
            'uri' => $uri,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'user_id' => $user ? $user->id : null,
            'user_type' => $user ? $user->type : null,
            'timestamp' => now()->toISOString(),
            'operation' => $this->getOperationName($method, $uri),
            'host' => $request->getHost(),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        Log::info('API Request Completed', [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $user ? $user->id : null,
            'ip' => $ip,
            'timestamp' => now()->toISOString(),
            'operation' => $this->getOperationName($method, $uri),
            'host' => $request->getHost(),
        ]);

        return $response;
    }

    /**
     * Détermine le nom de l'opération basée sur la méthode et l'URI
     */
    private function getOperationName(string $method, string $uri): string
    {
        // Extraire le chemin de l'URI (sans query parameters)
        $path = parse_url($uri, PHP_URL_PATH);

        // Déterminer l'opération basée sur la méthode et le chemin
        if (str_contains($path, '/comptes')) {
            switch ($method) {
                case 'GET':
                    if (preg_match('/\/comptes$/', $path)) {
                        return 'LIST_COMPTES';
                    } elseif (preg_match('/\/comptes\/[^\/]+$/', $path)) {
                        return 'GET_COMPTE';
                    }
                    break;
                case 'POST':
                    if (preg_match('/\/comptes$/', $path)) {
                        return 'CREATE_COMPTE';
                    }
                    break;
                case 'PUT':
                case 'PATCH':
                    return 'UPDATE_COMPTE';
                case 'DELETE':
                    return 'DELETE_COMPTE';
            }
        }

        if (str_contains($path, '/auth')) {
            switch ($method) {
                case 'POST':
                    if (str_contains($path, '/login')) {
                        return 'USER_LOGIN';
                    } elseif (str_contains($path, '/logout')) {
                        return 'USER_LOGOUT';
                    } elseif (str_contains($path, '/refresh')) {
                        return 'TOKEN_REFRESH';
                    }
                    break;
                case 'GET':
                    if (str_contains($path, '/user')) {
                        return 'GET_USER_INFO';
                    }
                    break;
            }
        }

        return strtoupper($method) . '_UNKNOWN';
    }
}