<?php

namespace App\Http\Requests;

use App\Messages;
use App\Rules\NciRule;
use App\Rules\TelephoneRule;
use Illuminate\Foundation\Http\FormRequest;

class CreateCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->type === 'admin';
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('Seul un administrateur peut créer des comptes');
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
            'devise' => 'nullable|string|in:FCFA,EUR,USD',
            'soldeInitial' => 'required|numeric|min:10000',
            'client' => 'required|array',
            'client.id' => 'nullable|uuid|exists:users,id',
            'client.titulaire' => 'required|string|max:255',
            'client.nci' => ['nullable', new NciRule()],
            'client.email' => 'required|email|unique:users,email',
            'client.telephone' => ['required', new TelephoneRule()],
            'client.adresse' => 'nullable|string|max:500',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => Messages::TYPE_COMPTE_OBLIGATOIRE->value,
            'type.in' => Messages::TYPE_COMPTE_INVALIDE->value,
            'soldeInitial.required' => Messages::SOLDE_INITIAL_OBLIGATOIRE->value,
            'soldeInitial.numeric' => Messages::SOLDE_INITIAL_NUMERIC->value,
            'soldeInitial.min' => Messages::SOLDE_INITIAL_MIN->value,
            'client.required' => Messages::CLIENT_OBLIGATOIRE->value,
            'client.array' => Messages::CLIENT_ARRAY->value,
            'client.titulaire.required' => Messages::NOM_OBLIGATOIRE->value,
            'client.titulaire.string' => Messages::NOM_STRING->value,
            'client.titulaire.max' => Messages::NOM_MAX->value,
            'client.email.required' => Messages::EMAIL_OBLIGATOIRE->value,
            'client.email.email' => Messages::EMAIL_VALIDE->value,
            'client.email.unique' => Messages::EMAIL_UNIQUE->value,
            'client.telephone.required' => Messages::TELEPHONE_OBLIGATOIRE->value,
            'client.adresse.string' => Messages::ADRESSE_STRING->value,
            'client.adresse.max' => Messages::ADRESSE_MAX->value,
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'client.titulaire' => 'titulaire du compte',
            'client.email' => 'email du client',
            'client.nci' => 'numéro de carte d\'identité',
            'client.telephone' => 'téléphone du client',
            'client.adresse' => 'adresse du client',
        ];
    }
}