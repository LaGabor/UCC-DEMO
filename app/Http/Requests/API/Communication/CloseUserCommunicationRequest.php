<?php

namespace App\Http\Requests\API\Communication;

use App\Http\Requests\API\FormRequest;

class CloseUserCommunicationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
