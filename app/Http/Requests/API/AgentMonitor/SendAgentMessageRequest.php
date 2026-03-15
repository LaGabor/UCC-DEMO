<?php

namespace App\Http\Requests\API\AgentMonitor;

use App\Http\Requests\API\FormRequest;

class SendAgentMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'message_text' => ['required', 'string', 'max:5000'],
        ];
    }
}
