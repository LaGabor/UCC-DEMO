<?php

namespace App\Http\Controllers\Api\Agent;

use App\Contracts\Services\ConversationHistoryServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ConversationHistoryController extends Controller
{
    public function __construct(
        private readonly ConversationHistoryServiceInterface $conversationHistoryService,
    ) {
    }

    public function index(): JsonResponse
    {
        $list = $this->conversationHistoryService->getConversationList(auth()->id());

        return ApiResponse::success($list->map(fn ($entry) => $entry->toArray())->values()->all());
    }

    public function show(int $conversationId): JsonResponse
    {
        $data = $this->conversationHistoryService->getFullConversationHistory(auth()->id(), $conversationId);

        return ApiResponse::success($data->toArray());
    }
}
