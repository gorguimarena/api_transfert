<?php

namespace App\Http\Requests;

use App\Messages;
use App\Rules\UserExisteRule;
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
            'type_compte' => 'required|in:epargne,cheque',
            'status_compte' => 'sometimes|in:active,bloque',
            'telephone' => 'required|string|max:20',
            'user_id' => ['required', 'uuid', new UserExisteRule()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type_compte.required' => Messages::TYPE_COMPTE_OBLIGATOIRE->value,
            'type_compte.in' => Messages::TYPE_COMPTE_INVALIDE->value,
            'status_compte.in' => Messages::STATUT_COMPTE_INVALIDE->value,
            'telephone.required' => Messages::TELEPHONE_OBLIGATOIRE->value,
            'user_id.uuid' => Messages::USER_ID_UUID->value,
            'user_id.required' => Messages::USER_ID_OBLIGATOIRE->value,
        ];
    }
}
