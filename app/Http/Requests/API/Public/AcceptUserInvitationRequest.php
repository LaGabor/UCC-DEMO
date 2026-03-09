<?php

namespace App\Http\Requests\Api\Public;

use App\Http\Requests\Api\FormRequest;

class AcceptUserInvitationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*[^A-Za-z0-9]).+$/',
            ],
            'preferred_locale' => ['required', 'in:hu,en'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter and one special character.',
        ];
    }
}
