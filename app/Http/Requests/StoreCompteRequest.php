<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

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
        return [
            'type' => 'required|in:epargne,cheque',
            'soldeInitial' => 'required|numeric|min:0', // Rendu obligatoire
            'devise' => 'required|string|size:3',
            'solde' => 'required|numeric|min:10000',
            'client' => 'required|array',
            'client.id' => 'nullable|integer|exists:clients,id',
            'client.titulaire' => 'required_without:client.id|string|max:255', // Requis si pas d'ID client
            'client.nci' => 'required_without:client.id|string|unique:clients,nci|regex:/^\d{13}$/', // Requis si pas d'ID client
            'client.email' => 'required_without:client.id|email|unique:clients,email', // Requis si pas d'ID client
            'client.telephone' => 'required_without:client.id|string|unique:clients,telephone|regex:/^(\+221|221)?7[0678]\d{7}$/', // Correction du délimiteur regex
            'client.adresse' => 'required_without:client.id|string', // Requis si pas d'ID client
        ];
    }

    public function withValidator($validator)
    {
        // Les règles 'required_without:client.id' gèrent déjà la logique conditionnelle.
        // Les règles 'unique' sont appliquées si le champ est présent et non nul.
        // Pas besoin de 'sometimes' ici car 'required_without' est plus direct.
    }
}
