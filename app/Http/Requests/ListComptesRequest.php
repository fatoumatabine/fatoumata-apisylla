<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListComptesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autoriser toutes les requêtes pour l'instant
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string', Rule::in(['epargne', 'cheque', 'courant'])], // Ajout de 'courant' comme type valide
            'statut' => ['nullable', 'string', Rule::in(['actif', 'bloque', 'ferme'])],
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['date_creation', 'solde', 'titulaire', 'numero_compte'])],
            'order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Le type de compte doit être "epargne", "cheque" ou "courant".',
            'statut.in' => 'Le statut du compte doit être "actif", "bloque" ou "ferme".',
            'sort.in' => 'Le champ de tri n\'est pas valide. Options: date_creation, solde, titulaire, numero_compte.',
            'order.in' => 'L\'ordre de tri doit être "asc" ou "desc".',
            'limit.min' => 'La limite doit être au moins de 1.',
            'limit.max' => 'La limite ne peut pas dépasser 100.',
        ];
    }
}
