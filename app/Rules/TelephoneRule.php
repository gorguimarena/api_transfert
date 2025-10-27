<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('Le numéro de téléphone doit être une chaîne de caractères.');
            return;
        }

        $value = str_replace(' ', '', $value);

        if (!str_starts_with($value, '+')) {
            $fail('Le numéro de téléphone doit commencer par +. Exemple: +221771234567');
            return;
        }

        $digitsOnly = substr($value, 1);
        if (!ctype_digit($digitsOnly)) {
            $fail('Le numéro de téléphone ne doit contenir que des chiffres après le +.');
            return;
        }

        if (strlen($digitsOnly) !== 12) { 
            $fail('Le numéro de téléphone doit contenir exactement 12 chiffres après le +. Exemple: +221771234567');
            return;
        }

        $validPrefixes = ['77', '78', '70', '75', '76'];
        $prefix = substr($digitsOnly, 3, 2); 

        if (!in_array($prefix, $validPrefixes)) {
            $fail('Le numéro de téléphone doit commencer par 77, 78, 70, 75 ou 76.');
        }
    }
}

