<?php

namespace App\Exceptions;

use Exception;

class CompteException extends Exception
{
    public function __construct(string $message = 'Erreur liée aux comptes', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function numeroCompteGenerationFailed(): self
    {
        return new self('Échec de la génération du numéro de compte', 500);
    }

    public static function clientNotFound(string $clientId): self
    {
        return new self("Client avec ID {$clientId} non trouvé", 404);
    }

    public static function invalidClientRelation(): self
    {
        return new self('Relation client invalide pour ce compte', 400);
    }

    public static function databaseError(string $details = ''): self
    {
        $message = 'Erreur de base de données lors de l\'opération sur les comptes';
        if ($details) {
            $message .= ': ' . $details;
        }
        return new self($message, 500);
    }

    public static function unauthorizedAccess(): self
    {
        return new self('Accès non autorisé à cette opération sur les comptes', 403);
    }
}