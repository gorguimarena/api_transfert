<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NciRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Vérifier que le NCI n'est pas vide
        if (empty($value)) {
            $fail('Le numéro de carte d\'identité nationale est obligatoire.');
            return;
        }

        // Vérifier le format du NCI sénégalais (généralement 13 chiffres ou format spécifique)
        // Format typique: 13 chiffres pour les nouvelles cartes, ou format avec lettres
        if (!preg_match('/^[A-Z0-9]{8,17}$/', $value)) {
            $fail('Le format du numéro de carte d\'identité nationale est invalide.');
            return;
        }

        // Vérifier que le NCI n'existe pas déjà dans la base de données
        $exists = \App\Models\Client::where('nci', $value)->exists();
        if ($exists) {
            $fail('Ce numéro de carte d\'identité nationale existe déjà.');
        }
    }
}