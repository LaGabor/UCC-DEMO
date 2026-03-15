<?php

namespace App\Http\Controllers\Api\Agent;

use App\Contracts\Services\AgentMonitorCommunicationServiceInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\AgentMonitor\SendAgentMessageRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentMonitorCommunicationController extends Controller
{
    public function __construct(
        private readonly AgentMonitorCommunicationServiceInterface $agentMonitorCommunicationService,
    ) {
    }

    public function viewUserChatHistory(Request $request, int $conversationId): JsonResponse
    {
        $data = $this->agentMonitorCommunicationService->viewUserChatHistory(
            $request->user()->id,
            $conversationId
        );

        return ApiResponse::success($data->toArray());
    }

    public function answerUserChatHistory(Request $request, int $conversationId): JsonResponse
    {
        $data = $this->agentMonitorCommunicationService->answerUserChatHistory(
            $request->user()->id,
            $conversationId
        );

        return ApiResponse::success($data->toArray());
    }

    public function closeAssigned(Request $request, int $conversationId): JsonResponse
    {
        $this->agentMonitorCommunicationService->closeAssigned(
            $request->user()->id,
            $conversationId
        );

        return ApiResponse::success(null, 'Conversation closed (assigned).');
    }

    public function closeWaitingHuman(Request $request, int $conversationId): JsonResponse
    {
        $this->agentMonitorCommunicationService->closeWaitingHuman(
            $request->user()->id,
            $conversationId
        );

        return ApiResponse::success(null, 'Conversation closed (waiting human).');
    }

    public function closeAgentCommunicationStatus(Request $request): JsonResponse
    {
        $this->agentMonitorCommunicationService->closeAgentCommunicationStatus(
            $request->user()->id
        );

        return ApiResponse::success(null, 'Agent communication status closed.');
    }

    public function sendAgentMessage(SendAgentMessageRequest $request, int $conversationId): JsonResponse
    {
        $this->agentMonitorCommunicationService->sendAgentMessage(
            $request->user()->id,
            $conversationId,
            $request->validated('message_text')
        );

        return ApiResponse::success(null, 'Message sent.');
    }
}
