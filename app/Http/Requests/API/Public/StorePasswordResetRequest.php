<?php

namespace App\Http\Requests\Api\Public;

use App\Http\Requests\Api\FormRequest;

class StorePasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }
}
