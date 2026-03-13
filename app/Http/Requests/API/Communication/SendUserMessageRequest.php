<?php

namespace App\Http\Requests\API\Communication;

use App\Enums\ConversationStatus;
use App\Http\Requests\API\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SendUserMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'conversation_status' => [
                'required',
                new Enum(ConversationStatus::class),
                Rule::in([
                    ConversationStatus::OPEN->value,
                    ConversationStatus::WAITING_HUMAN->value,
                    ConversationStatus::ASSIGNED->value,
                    ConversationStatus::CLOSED->value,
                ]),
            ],
            'message_text' => ['required', 'string', 'max:5000'],
        ];
    }
}
