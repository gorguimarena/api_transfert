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
        if (empty($value)) {
            $fail('Le numéro de carte d\'identité nationale est obligatoire.');
            return;
        }

        if (!preg_match('/^[A-Z0-9]{8,17}$/', $value)) {
            $fail('Le format du numéro de carte d\'identité nationale est invalide.');
            return;
        }

        $exists = \App\Models\Client::where('nci', $value)->exists();
        if ($exists) {
            $fail('Ce numéro de carte d\'identité nationale existe déjà.');
        }
    }
}