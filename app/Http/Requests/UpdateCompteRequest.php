<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'titulaire' => 'sometimes|string|max:255',
            'informationsClient' => 'sometimes|array',
            'informationsClient.telephone' => 'sometimes|string|regex:/^(\+221|221)?7[0678]\d{7}$/',
            'informationsClient.email' => 'sometimes|email',
            'informationsClient.password' => 'sometimes|string|min:8',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier qu'au moins un champ de modification est fourni
            $fields = ['titulaire', 'informationsClient'];
            $hasAtLeastOneField = false;

            foreach ($fields as $field) {
                if ($this->has($field)) {
                    $hasAtLeastOneField = true;
                    break;
                }
            }

            if (!$hasAtLeastOneField) {
                $validator->errors()->add('general', 'Au moins un champ de modification est requis.');
            }
        });
    }
}
