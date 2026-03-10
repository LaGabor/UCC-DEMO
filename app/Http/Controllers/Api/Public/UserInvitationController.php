<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Data\Public\AcceptUserInvitationData;
use App\Http\Requests\API\Public\AcceptUserInvitationRequest;

class UserInvitationController extends Controller
{
    public function __construct(
        private readonly UserInvitationServiceInterface $invitationService
    ) {}

    public function show(string $token)
    {
        $invitation = $this->invitationService->getInvitationByToken($token);

        return response()->json($invitation->toArray());
    }

    public function accept(
        AcceptUserInvitationRequest $request,
        string $token
    ) {
        $data = AcceptUserInvitationData::fromRequest($request, $token);

        $this->invitationService->accept($data);

        return response()->json([
            'message' => 'Invitation accepted successfully.'
        ]);
    }
}
