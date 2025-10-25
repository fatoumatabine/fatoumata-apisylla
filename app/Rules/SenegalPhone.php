<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SenegalPhone implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!preg_match('/^(\+221|221)?7[0678]\d{7}$/', $value)) {
            $fail('Le numéro de téléphone doit être un numéro valide du Sénégal.');
        }
    }
}
