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

    // Messages de validation pour les clients
    case NOM_OBLIGATOIRE = 'Le nom est obligatoire';
    case NOM_STRING = 'Le nom doit être une chaîne de caractères';
    case NOM_MAX = 'Le nom ne peut pas dépasser 255 caractères';
    case EMAIL_OBLIGATOIRE = 'L\'email est obligatoire';
    case PASSWORD_OBLIGATOIRE = 'Le mot de passe est obligatoire';
    case PASSWORD_STRING = 'Le mot de passe doit être une chaîne de caractères';
    case PASSWORD_MIN = 'Le mot de passe doit contenir au moins 8 caractères';

    // Messages de validation pour les règles personnalisées
    case TELEPHONE_STRING = 'Le numéro de téléphone doit être une chaîne de caractères.';
    case TELEPHONE_PLUS = 'Le numéro de téléphone doit commencer par +. Exemple: +221771234567';
    case TELEPHONE_CHIFFRES_SEULEMENT = 'Le numéro de téléphone ne doit contenir que des chiffres après le +. Exemple: +221771234567';
    case TELEPHONE_LONGUEUR = 'Le numéro de téléphone doit contenir exactement 9 chiffres après +221.';
    case TELEPHONE_PREFIXE = 'Le numéro de téléphone doit commencer par 77, 78, 70, 75 ou 76.';

    // Messages pour NCI
    case NCI_OBLIGATOIRE = 'Le numéro de carte d\'identité nationale est obligatoire.';
    case NCI_FORMAT_INVALIDE = 'Le format du numéro de carte d\'identité nationale est invalide.';
    case NCI_DEJA_EXISTE = 'Ce numéro de carte d\'identité nationale existe déjà.';

    // Messages pour les comptes
    case SOLDE_INITIAL_OBLIGATOIRE = 'Le solde initial est obligatoire';
    case SOLDE_INITIAL_NUMERIC = 'Le solde initial doit être un nombre';
    case SOLDE_INITIAL_MIN = 'Le solde initial doit être supérieur ou égal à 0';
    case CLIENT_OBLIGATOIRE = 'Les informations du client sont obligatoires';
    case CLIENT_ARRAY = 'Les informations du client doivent être un objet';
    case EMAIL_VALIDE = 'L\'email doit être une adresse email valide';
    case EMAIL_UNIQUE = 'Cet email est déjà utilisé';
    case ADRESSE_STRING = 'L\'adresse doit être une chaîne de caractères';
    case ADRESSE_MAX = 'L\'adresse ne peut pas dépasser 500 caractères';

    case EMAIL_STRING = 'L\'email doit être une chaîne de caractères.';
    case EMAIL_AROBASE = 'L\'email doit contenir le caractère @.';
    case EMAIL_AROBASE_UNIQUE = 'L\'email ne doit contenir qu\'un seul caractère @.';
    case EMAIL_LOCAL_VIDE = 'La partie avant @ ne peut pas être vide.';
    case EMAIL_DOMAINE_VIDE = 'La partie après @ ne peut pas être vide.';
    case EMAIL_DOMAINE_POINT = 'Le domaine doit contenir au moins un point. Exemple: gmail.com';
    case EMAIL_ESPACES = 'L\'email ne doit pas contenir d\'espaces.';
    case EMAIL_LONGUEUR = 'L\'email est trop long (maximum 254 caractères).';

    // Messages d'authentification
    case IDENTIFIANTS_INVALIDES = 'Identifiants invalides';
    case ERREUR_GENERATION_TOKEN = 'Erreur lors de la génération du token';
    case CONNEXION_REUSSIE = 'Connexion réussie';
    case REFRESH_TOKEN_MANQUANT = 'Refresh token manquant';
    case CONFIGURATION_OAUTH_INVALIDE = 'Configuration OAuth invalide';
    case REFRESH_TOKEN_INVALIDE = 'Refresh token invalide';
    case TOKEN_RENOUVELE = 'Token renouvelé avec succès';
    case UTILISATEUR_NON_AUTHENTIFIE = 'Utilisateur non authentifié';
    case DECONNEXION_REUSSIE = 'Déconnexion réussie';

    // Messages de validation pour l'authentification
    case EMAIL_OBLIGATOIRE = 'L\'adresse email est obligatoire';
    case EMAIL_VALIDE = 'L\'adresse email doit être valide';
    case PASSWORD_OBLIGATOIRE = 'Le mot de passe est obligatoire';
    case PASSWORD_STRING = 'Le mot de passe doit être une chaîne de caractères';
    case PASSWORD_MIN = 'Le mot de passe doit contenir au moins 8 caractères';
    case REMEMBER_BOOLEAN = 'Le champ remember doit être un booléen';
    case ERREUR_VALIDATION = 'Erreur de validation';
}
