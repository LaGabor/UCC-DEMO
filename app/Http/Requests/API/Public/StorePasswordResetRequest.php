<?php

namespace App\Http\Requests\API\Public;

use App\Http\Requests\API\FormRequest;

class StorePasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc,dns', 'max:255'],
        ];
    }
}
