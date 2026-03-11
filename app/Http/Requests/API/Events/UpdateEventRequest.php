<?php

namespace App\Http\Requests\API\Events;

use App\Http\Requests\API\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
        ];
    }
}

