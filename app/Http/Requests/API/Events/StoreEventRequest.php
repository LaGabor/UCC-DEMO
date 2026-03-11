<?php

namespace App\Http\Requests\API\Events;

use App\Http\Requests\API\FormRequest;

class StoreEventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'occurs_at' => ['required', 'date', 'after:now'],
        ];
    }
}

