<?php

namespace App;

enum Messages: string
{
    // Messages de succès
    case COMPTE_CREE = 'Compte créé avec succès';
    case COMPTES_RECUPERES = 'Comptes récupérés avec succès';

    // Messages d'erreur
    case ERREUR_CREATION_COMPTE = 'Erreur lors de la création du compte';
    case ERREUR_INTERNE = 'Erreur interne du serveur';
    case UTILISATEUR_NON_CLIENT = 'L\'utilisateur spécifié n\'est pas un client';
    case PROFIL_CLIENT_MANQUANT = 'Aucun profil client trouvé pour cet utilisateur';

    // Messages de validation
    case NUMERO_COMPTE_OBLIGATOIRE = 'Le numéro de compte est obligatoire.';
    case NUMERO_COMPTE_UNIQUE = 'Ce numéro de compte existe déjà.';
    case TYPE_COMPTE_OBLIGATOIRE = 'Le type de compte est obligatoire.';
    case TYPE_COMPTE_INVALIDE = 'Le type de compte doit être épargne ou chèque.';
    case STATUT_COMPTE_INVALIDE = 'Le statut doit être actif ou bloqué.';
    case TELEPHONE_OBLIGATOIRE = 'Le numéro de téléphone est obligatoire.';
    case USER_ID_OBLIGATOIRE = 'L\'ID utilisateur est obligatoire.';
    case USER_ID_UUID = 'L\'ID utilisateur doit être un UUID valide.';
}
