<?php

namespace App\Http\Requests\API\Public;

use App\Enums\Language;
use App\Http\Requests\API\FormRequest;
use Illuminate\Validation\Rules\Enum;

class AcceptUserInvitationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:4', 'max:50'],
            'password' => [
                'required',
                'string',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
            'preferred_locale' => ['required', new Enum(Language::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'The password must contain at least one letter, one number, and one special character.',
        ];
    }
}
