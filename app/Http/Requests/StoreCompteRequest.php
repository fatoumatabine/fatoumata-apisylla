<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class StoreCompteRequest extends FormRequest
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
        $rules = [
            'type' => 'required|in:epargne,cheque',
            'solde' => 'required|numeric|min:10000',
            'devise' => 'required|string|size:3',
            'client' => 'required|array',
            'client.id' => 'nullable|string|uuid|exists:clients,id',
            'client.titulaire' => 'required_without:client.id|string|max:255',
            'client.nci' => [
                'required_without:client.id',
                'string',
                'regex:/^\d{13}$/',
                Rule::unique('clients', 'nci')->ignore($this->input('client.id'), 'id'),
            ],
            'client.email' => [
                'required_without:client.id',
                'email',
                Rule::unique('clients', 'email')->ignore($this->input('client.id'), 'id'),
            ],
            'client.telephone' => [
                'required_without:client.id',
                'string',
                'regex:/^(\+221|221)?7[0678]\d{7}$/',
                Rule::unique('clients', 'telephone')->ignore($this->input('client.id'), 'id'),
            ],
            'client.adresse' => 'required_without:client.id|string',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être "epargne" ou "cheque".',
            'solde.required' => 'Le solde initial est obligatoire.',
            'solde.numeric' => 'Le solde doit être un nombre.',
            'solde.min' => 'Le solde initial doit être d\'au moins 10000.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.size' => 'La devise doit avoir 3 caractères (ex: FCFA).',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un tableau.',
            'client.id.integer' => 'L\'ID du client doit être un entier.',
            'client.id.exists' => 'L\'ID du client fourni n\'existe pas.',
            'client.titulaire.required' => 'Le nom du titulaire est obligatoire si le client n\'existe pas.',
            'client.titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne doit pas dépasser 255 caractères.',
            'client.nci.required' => 'Le numéro NCI est obligatoire si le client n\'existe pas.',
            'client.nci.string' => 'Le numéro NCI doit être une chaîne de caractères.',
            'client.nci.regex' => 'Le numéro NCI doit contenir exactement 13 chiffres.',
            'client.nci.unique' => 'Ce numéro NCI est déjà utilisé par un autre client.',
            'client.email.required' => 'L\'adresse email est obligatoire si le client n\'existe pas.',
            'client.email.email' => 'L\'adresse email doit être une adresse email valide.',
            'client.email.unique' => 'Cette adresse email est déjà utilisée par un autre client.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire si le client n\'existe pas.',
            'client.telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'client.telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide (ex: +221771234567 ou 771234567).',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé par un autre client.',
            'client.adresse.required' => 'L\'adresse est obligatoire si le client n\'existe pas.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
        ];
    }
}
