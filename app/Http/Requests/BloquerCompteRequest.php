<?php

namespace App\Http\Requests;

use App\Messages;
use Illuminate\Foundation\Http\FormRequest;

class BloquerCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('api')->check() && auth('api')->user()->type === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'block_reason' => 'required|string|max:500',
            'block_start_date' => 'nullable|date|after_or_equal:today',
            'block_duration_days' => 'nullable|integer|min:1|max:365',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'block_reason.required' => 'La raison du blocage est obligatoire',
            'block_reason.string' => 'La raison du blocage doit être une chaîne de caractères',
            'block_reason.max' => 'La raison du blocage ne peut pas dépasser 500 caractères',
            'block_start_date.date' => 'La date de début du blocage doit être une date valide',
            'block_start_date.after_or_equal' => 'La date de début du blocage ne peut pas être dans le passé',
            'block_duration_days.integer' => 'La durée du blocage doit être un nombre entier',
            'block_duration_days.min' => 'La durée du blocage doit être d\'au moins 1 jour',
            'block_duration_days.max' => 'La durée du blocage ne peut pas dépasser 365 jours',
        ];
    }
}
