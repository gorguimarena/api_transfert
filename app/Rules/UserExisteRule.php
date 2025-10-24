<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserExisteRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = User::where('id', $value)->where('type', 'client')->first();

        if (!$user) {
            $fail('L\'utilisateur spécifié n\'existe pas ou n\'est pas un client.');
            return;
        }

        if (!$user->client) {
            $fail('L\'utilisateur spécifié n\'a pas de profil client.');
            return;
        }
    }
}
