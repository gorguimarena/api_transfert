<?php

namespace App\Rules;

use App\Messages;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail(Messages::TELEPHONE_STRING->value);
            return;
        }

        $value = str_replace(' ', '', $value);

        if (!str_starts_with($value, '+')) {
            $fail(Messages::TELEPHONE_PLUS->value);
            return;
        }

        $digitsOnly = substr($value, 1);
        if (!ctype_digit($digitsOnly)) {
            $fail(Messages::TELEPHONE_CHIFFRES_SEULEMENT->value);
            return;
        }

        if (strlen($digitsOnly) !== 12) {
            $fail(Messages::TELEPHONE_LONGUEUR->value);
            return;
        }

        $validPrefixes = ['77', '78', '70', '75', '76'];
        $prefix = substr($digitsOnly, 3, 2);

        if (!in_array($prefix, $validPrefixes)) {
            $fail(Messages::TELEPHONE_PREFIXE->value);
        }
    }
}

