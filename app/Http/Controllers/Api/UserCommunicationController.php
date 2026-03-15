<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\UserCommunicationServiceInterface;
use App\Data\Communication\CloseUserCommunicationInputData;
use App\Data\Communication\UpdateConversationStatusData;
use App\Data\Communication\UserMessageInputData;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Communication\CloseUserCommunicationRequest;
use App\Http\Requests\API\Communication\SendUserMessageRequest;
use App\Http\Requests\API\Communication\UpdateConversationStatusRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserCommunicationController extends Controller
{
    public function __construct(
        private readonly UserCommunicationServiceInterface $userCommunicationService,
    ) {
    }

    public function getUserConversation(): JsonResponse
    {
        $payload = $this->userCommunicationService->getUserConversation(auth()->id());

        return ApiResponse::success($payload->toArray());
    }

    public function sendUserMessage(SendUserMessageRequest $request): JsonResponse
    {
        $payload = $this->userCommunicationService->createUserMessage(
            $request->user()->id,
            UserMessageInputData::fromRequest($request)
        );

        return ApiResponse::success($payload->toArray(), 'Message accepted.');
    }

    public function callAgent(UpdateConversationStatusRequest $request): JsonResponse
    {
        $payload = $this->userCommunicationService->callAgent(
            $request->user()->id,
            UpdateConversationStatusData::fromRequest($request)
        );

        return ApiResponse::success($payload->toArray());
    }

    public function cancelCall(UpdateConversationStatusRequest $request): JsonResponse
    {
        $payload = $this->userCommunicationService->cancelCall(
            $request->user()->id,
            UpdateConversationStatusData::fromRequest($request)
        );

        return ApiResponse::success($payload->toArray());
    }

    public function closeUserCommunication(CloseUserCommunicationRequest $request): JsonResponse
    {
        $payload = $this->userCommunicationService->closeUserCommunication(
            $request->user()->id,
            CloseUserCommunicationInputData::fromRequest($request)
        );

        return ApiResponse::success($payload->toArray());
    }
}
