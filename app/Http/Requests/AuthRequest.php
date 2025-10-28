<?php

namespace App\Http\Requests;

use App\Messages;
use App\ResponseTrait;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class AuthRequest extends FormRequest
{
    use ResponseTrait;

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
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'remember' => 'boolean'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => Messages::EMAIL_OBLIGATOIRE->value,
            'email.email' => Messages::EMAIL_VALIDE->value,
            'password.required' => Messages::PASSWORD_OBLIGATOIRE->value,
            'password.string' => Messages::PASSWORD_STRING->value,
            'password.min' => Messages::PASSWORD_MIN->value,
            'remember.boolean' => Messages::REMEMBER_BOOLEAN->value,
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            $this->errorResponse(Messages::ERREUR_VALIDATION->value, 422, $validator->errors())
        );
    }
}
