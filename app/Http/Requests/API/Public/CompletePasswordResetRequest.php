<?php

namespace App\Http\Requests\API\Public;

use App\Http\Requests\API\FormRequest;

class CompletePasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter, one number, and one special character.',
        ];
    }
}
