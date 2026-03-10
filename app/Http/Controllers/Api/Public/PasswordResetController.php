<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Data\Public\CreatePasswordResetRequestData;
use App\Data\Public\CompletePasswordResetData;
use App\Http\Requests\API\Public\StorePasswordResetRequest;
use App\Http\Requests\API\Public\CompletePasswordResetRequest;

class PasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordResetService
    ) {}

    public function store(StorePasswordResetRequest $request)
    {
        $data = CreatePasswordResetRequestData::fromRequest($request);

        $this->passwordResetService->create($data);

        return response()->json([
            'message' => 'Password reset request created.'
        ]);
    }

    public function show(string $token)
    {
        $result = $this->passwordResetService
            ->getResetRequestByToken($token);

        return response()->json($result->toArray());
    }

    public function update(
        CompletePasswordResetRequest $request,
        string $token
    ) {
        $data = CompletePasswordResetData::fromRequest($request, $token);

        $this->passwordResetService->complete($data);

        return response()->json([
            'message' => 'Password successfully reset.'
        ]);
    }
}
