<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth('api')->user();

        if (!$user || $user->type !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé pour cette action'
            ], 403);
        }

        return $next($request);
    }
}
