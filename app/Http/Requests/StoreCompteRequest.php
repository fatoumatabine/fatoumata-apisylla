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
            'solde' => 'required|numeric|min:0',
            'devise' => 'required|string|size:3',
            'client' => 'required|array',
            'client.id' => 'nullable|integer|exists:clients,id',
            'client.titulaire' => 'required_without:client.id|string|max:255',
            'client.nci' => 'required_without:client.id|string|unique:clients,nci|regex:/^\d{13}$/',
            'client.email' => 'required_without:client.id|email|unique:clients,email',
            'client.telephone' => 'required_without:client.id|string|unique:clients,telephone|regex:/^(\+221|221)?7[0678]\d{7}$/',
            'client.adresse' => 'required_without:client.id|string',
        ];

        // Si client.id n'est pas fourni, les champs du client sont requis et uniques
        if (!$this->input('client.id')) {
            $rules['client.titulaire'] = 'required|string|max:255';
            $rules['client.nci'] = [
                'required',
                'string',
                'regex:/^\d{13}$/',
                'unique:clients,nci',
            ];
            $rules['client.email'] = [
                'required',
                'email',
                'unique:clients,email',
            ];
            $rules['client.telephone'] = [
                'required',
                'string',
                'regex:/^(\+221|221)?7[0678]\d{7}$/',
                'unique:clients,telephone',
            ];
            $rules['client.adresse'] = 'required|string';
        } else {
            // Si client.id est fourni, les autres champs du client sont facultatifs et ne sont pas validés pour l'unicité
            $rules['client.titulaire'] = 'nullable|string|max:255';
            $rules['client.nci'] = [
                'nullable',
                'string',
                'regex:/^\d{13}$/',
            ];
            $rules['client.email'] = [
                'nullable',
                'email',
            ];
            $rules['client.telephone'] = [
                'nullable',
                'string',
                'regex:/^(\+221|221)?7[0678]\d{7}$/',
            ];
            $rules['client.adresse'] = 'nullable|string';
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        // La logique de validation conditionnelle est maintenant gérée directement dans la méthode rules().
        // Cette méthode peut être laissée vide ou utilisée pour des validations plus complexes si nécessaire.
    }
}
