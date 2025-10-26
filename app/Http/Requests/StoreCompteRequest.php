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
            'soldeInitial' => 'nullable|numeric|min:0', // peut-être pas utilisé, mais dans le body
            'devise' => 'required|string|size:3',
            'solde' => 'required|numeric|min:10000',
            'client' => 'required|array',
            'client.id' => 'nullable|integer|exists:clients,id',
            'client.titulaire' => 'nullable|string|max:255',
            'client.nci' => 'nullable|string',
            'client.email' => 'nullable|email',
            'client.telephone' => 'nullable|string',
            'client.adresse' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->sometimes('client.titulaire', 'required', function ($input) {
            return !isset($input->client['id']);
        });

        $validator->sometimes('client.nci', 'required|unique:clients,nci|regex:/^\d{13}$/', function ($input) {
            return !isset($input->client['id']);
        });

        $validator->sometimes('client.email', 'required|email|unique:clients,email', function ($input) {
            return !isset($input->client['id']);
        });

        $validator->sometimes('client.telephone', 'required|unique:clients,telephone|regex:/^(\+221|221)?7[0678]\d{7}$/', function ($input) {
            return !isset($input->client['id']);
        });

        $validator->sometimes('client.adresse', 'required', function ($input) {
            return !isset($input->client['id']);
        });
    }
}
