<?php

namespace App\Http\Requests\API\User;

use App\Enums\Language;
use App\Http\Requests\API\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePreferredLocaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'preferred_locale' => [
                'required',
                new Enum(Language::class),
            ],
        ];
    }
}

