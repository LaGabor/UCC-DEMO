<?php

namespace App\Services;

use App\Contracts\Services\LargeLanguageMessageResponderServiceInterface;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OllamaResponderService implements LargeLanguageMessageResponderServiceInterface
{
    public function resolve(Conversation $conversation, ConversationMessage $userMessage): ?string
    {
        $fallback = (string) config('communication.ollama.fallback_message', 'The assistant is temporarily unavailable. Please try again later.');

        try {
            $timeout = max(60, (int) config('communication.ollama.timeout_seconds', 60));
            $response = Http::timeout($timeout)
                ->post((string) config('communication.ollama.url'), [
                    'model' => config('communication.ollama.model'),
                    'prompt' => $userMessage->message_text ?? '',
                    'stream' => false,
                ]);

            $response->throw();

            $responseBody = $response->json();
            $responseText = $response->json('response');

            Log::info('communication.ollama.response', [
                'conversation_id' => $conversation->id,
                'user_message_id' => $userMessage->id,
                'status' => $response->status(),
                'raw_body' => $responseBody,
                'response_field' => $responseText,
            ]);

            if (is_string($responseText) && $responseText !== '') {
                return trim($responseText);
            }

            Log::warning('communication.ollama.empty_response', [
                'conversation_id' => $conversation->id,
                'user_message_id' => $userMessage->id,
                'raw_body' => $responseBody,
            ]);

            return $fallback;
        } catch (Throwable $exception) {
            Log::warning('communication.ollama.resolve_failed', [
                'conversation_id' => $conversation->id,
                'user_message_id' => $userMessage->id,
                'error' => $exception->getMessage(),
            ]);

            return $fallback;
        }
    }
}
