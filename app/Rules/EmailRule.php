<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('L\'email doit être une chaîne de caractères.');
            return;
        }

        $value = trim($value);

        if (!str_contains($value, '@')) {
            $fail('L\'email doit contenir le caractère @.');
            return;
        }

        $parts = explode('@', $value);
        if (count($parts) !== 2) {
            $fail('L\'email ne doit contenir qu\'un seul caractère @.');
            return;
        }

        $local = $parts[0];
        $domain = $parts[1];

        if (empty($local)) {
            $fail('La partie avant @ ne peut pas être vide.');
            return;
        }

        if (empty($domain)) {
            $fail('La partie après @ ne peut pas être vide.');
            return;
        }

        if (!str_contains($domain, '.')) {
            $fail('Le domaine doit contenir au moins un point. Exemple: gmail.com');
            return;
        }

        if (str_contains($local, ' ') || str_contains($domain, ' ')) {
            $fail('L\'email ne doit pas contenir d\'espaces.');
            return;
        }

        if (strlen($value) > 254) {
            $fail('L\'email est trop long (maximum 254 caractères).');
            return;
        }
    }
}
