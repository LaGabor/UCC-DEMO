<?php

namespace App\Http\Requests\API\Admin;

use App\Enums\UserRole;
use App\Http\Requests\API\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreUserInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
            ],
            'role' => [
                'required',
                new Enum(UserRole::class),
            ],
        ];
    }
}
