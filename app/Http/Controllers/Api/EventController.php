<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Services\EventServiceInterface;
use App\Data\Events\EventFiltersData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Events\IndexEventRequest;
use App\Http\Requests\API\Events\StoreEventRequest;
use App\Http\Requests\API\Events\UpdateEventRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EventController extends Controller
{
    public function __construct(
        private readonly EventServiceInterface $eventService,
    ) {
    }

    public function index(IndexEventRequest $request): JsonResponse
    {
        $result = $this->eventService->listForUser(
            $request->user()->id,
            EventFiltersData::fromRequest($request)
        );

        return response()->json($result->toArray());
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $created = $this->eventService->createForUser(
            $request->user()->id,
            UpsertEventData::fromStoreRequest($request)
        );

        return ApiResponse::success(
            $created->toArray(),
            'Event created successfully.',
            Response::HTTP_CREATED
        );
    }

    public function show(Request $request, int $event): JsonResponse
    {
        $record = $this->eventService->getForUser(
            $request->user()->id,
            $event
        );

        return ApiResponse::success($record->toArray());
    }

    public function update(UpdateEventRequest $request, int $event): JsonResponse
    {
        $updated = $this->eventService->updateForUser(
            $request->user()->id,
            $event,
            UpdateEventDescriptionData::fromRequest($request)
        );

        return ApiResponse::success(
            $updated->toArray(),
            'Event updated successfully.'
        );
    }

    public function destroy(Request $request, int $event): JsonResponse
    {
        $this->eventService->deleteForUser($request->user()->id, $event);

        return ApiResponse::success(null, 'Event deleted successfully.');
    }
}

