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
            'soldeInitial' => 'required|numeric|min:0',
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
            'type_compte.required' => Messages::TYPE_COMPTE_OBLIGATOIRE->value,
            'type_compte.in' => Messages::TYPE_COMPTE_INVALIDE->value,
            'soldeInitial.required' => 'Le solde initial est obligatoire',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 0',
            'client.required' => 'Les informations du client sont obligatoires',
            'client.array' => 'Les informations du client doivent être un objet',
            'client.nom.required' => Messages::NOM_OBLIGATOIRE->value,
            'client.nom.string' => Messages::NOM_STRING->value,
            'client.nom.max' => Messages::NOM_MAX->value,
            'client.prenom.required' => 'Le prénom est obligatoire',
            'client.prenom.string' => 'Le prénom doit être une chaîne de caractères',
            'client.prenom.max' => 'Le prénom ne peut pas dépasser 255 caractères',
            'client.email.required' => Messages::EMAIL_OBLIGATOIRE->value,
            'client.email.email' => 'L\'email doit être une adresse email valide',
            'client.email.unique' => 'Cet email est déjà utilisé',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères',
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