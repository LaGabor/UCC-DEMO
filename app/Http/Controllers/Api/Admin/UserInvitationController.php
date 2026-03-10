<?php
namespace App\Http\Controllers\Api\Admin;

use App\Data\Admin\CreateUserInvitationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Admin\StoreUserInvitationRequest;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UserInvitationController extends Controller
{
    public function __construct(
        private readonly UserInvitationServiceInterface $userInvitationService,
    ) {
    }

    public function store(StoreUserInvitationRequest $request): JsonResponse
    {
        $data = CreateUserInvitationData::fromRequest($request);

        $this->userInvitationService->create($data);

        return ApiResponse::success(
            null,
            'Invitation process started. Delivery continues in the background.',
            Response::HTTP_CREATED
        );
    }
}
