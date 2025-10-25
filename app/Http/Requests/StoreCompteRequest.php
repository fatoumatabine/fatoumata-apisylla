<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'soldeInitial' => 'nullable|numeric|min:0', // peut-être pas utilisé, mais dans le body
            'devise' => 'required|string|size:3',
            'solde' => 'required|numeric|min:10000',
            'client' => 'required|array',
            'client.id' => 'nullable|integer|exists:clients,id',
            'client.titulaire' => 'required|string|max:255',
            'client.nci' => 'required|unique:clients,nci',
            'client.email' => 'required|email|unique:clients,email',
            'client.telephone' => 'required|unique:clients,telephone',
            'client.adresse' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->addRules([
            'client.nci' => ['required', new \App\Rules\SenegalNci],
            'client.telephone' => ['required', new \App\Rules\SenegalPhone],
        ]);
    }
}
