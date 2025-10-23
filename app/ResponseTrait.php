<?php

namespace App;
use Illuminate\Http\JsonResponse;

trait ResponseTrait
{
    /**
     * Réponse de succès standardisée
     */
    protected function successResponse($data = null, string $message = 'Opération réussie', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Réponse d’erreur standardisée
     */
    protected function errorResponse(string $message = 'Erreur interne du serveur', int $code = 500, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Réponse de validation
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse('Erreur de validation', 422, $errors);
    }

    /**
     * Réponse quand une ressource n’est pas trouvée
     */
    protected function notFoundResponse(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }
}
