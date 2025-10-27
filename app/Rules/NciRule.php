<?php

namespace App\Rules;

use App\Messages;
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
            return; 
        }

        if (!preg_match('/^[A-Z0-9]{8,17}$/', $value)) {
            $fail(Messages::NCI_FORMAT_INVALIDE->value);
            return;
        }

        $exists = \App\Models\Client::where('nci', $value)->exists();
        if ($exists) {
            $fail(Messages::NCI_DEJA_EXISTE->value);
        }
    }
}