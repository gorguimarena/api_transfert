<?php

namespace App\Http\Requests;

use App\Messages;
use App\Rules\EmailRule;
use App\Rules\TelephoneRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => ['required', new EmailRule()],
            'password' => 'required|string|min:8',
            'telephone' => ['nullable', new TelephoneRule()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => Messages::NOM_OBLIGATOIRE->value,
            'name.string' => Messages::NOM_STRING->value,
            'name.max' => Messages::NOM_MAX->value,
            'email.required' => Messages::EMAIL_OBLIGATOIRE->value,
            'password.required' => Messages::PASSWORD_OBLIGATOIRE->value,
            'password.string' => Messages::PASSWORD_STRING->value,
            'password.min' => Messages::PASSWORD_MIN->value,
        ];
    }
}
