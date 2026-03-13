<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\AgentMonitorServiceInterface;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AgentMonitorController extends Controller
{
    public function __construct(
        private readonly AgentMonitorServiceInterface $agentMonitorService,
    ) {
    }

    public function index(): JsonResponse
    {
        $conversations = $this->agentMonitorService->getNonClosedConversations(auth()->id());

        return ApiResponse::success(
            $conversations->map(fn ($dto) => $dto->toArray())->values()->all()
        );
    }
}
