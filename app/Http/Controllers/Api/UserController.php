<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\UserServiceInterface;
use App\Data\User\UpdatePreferredLocaleData;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\User\UpdatePreferredLocaleRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly UserServiceInterface $userService,
    ) {
    }

    public function updatePreferredLocale(UpdatePreferredLocaleRequest $request): JsonResponse
    {
        $this->userService->updatePreferredLocale(
            $request->user()->id,
            UpdatePreferredLocaleData::fromRequest($request)
        );

        return ApiResponse::success(
            null,
            'Preferred locale updated successfully.'
        );
    }
}

