<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BodyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:10', 'regex:/^[A-Za-z0-9+\/=]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'O campo body é obrigatório.',
            'body.string' => 'O campo body deve ser uma string.',
            'body.min' => 'O campo body deve ter pelo menos :min caracteres.',
            'body.regex' => 'O campo body contém caracteres inválidos. Deve ser base64 válido.',
        ];
    }
}
