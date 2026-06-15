<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Availability\AvailabilityDayRequest;
use App\Repositories\Contracts\BookableResourceRepositoryInterface;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Availability')]
class AvailabilityController extends Controller
{
    public function __construct(
        private readonly BookableResourceRepositoryInterface $resources,
        private readonly AvailabilityService $availability,
    ) {}

    #[OA\Get(path: '/api/v1/resources/{id}/availability', tags: ['Availability'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function day(AvailabilityDayRequest $request, int $id): JsonResponse
    {
        $model = $this->resources->findById($id);
        if (! $model || ! $model->is_active) {
            return response()->json(['message' => 'Not found.', 'error' => 'not_found'], 404);
        }

        $day = Carbon::parse($request->validated('date'), config('app.timezone'))->startOfDay();
        $bookedRaw = $this->availability->bookedWindowsForDay($id, $day);

        $booked = array_map(static fn (array $w) => [
            'start' => Carbon::parse($w['starts_at'])->toIso8601String(),
            'end' => Carbon::parse($w['ends_at'])->toIso8601String(),
        ], $bookedRaw);

        return response()->json([
            'resource_id' => $model->id,
            'date' => $day->toDateString(),
            'booked' => $booked,
        ]);
    }

    #[OA\Get(path: '/api/v1/resources/{id}/suggested-slots', tags: ['Availability'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function slots(AvailabilityDayRequest $request, int $id): JsonResponse
    {
        $model = $this->resources->findById($id);
        if (! $model || ! $model->is_active) {
            return response()->json(['message' => 'Not found.', 'error' => 'not_found'], 404);
        }

        $day = Carbon::parse($request->validated('date'), config('app.timezone'))->startOfDay();
        $slotMinutes = (int) ($model->slot_duration_minutes ?: config('booking.default_slot_minutes', 60));
        $slots = $this->availability->suggestSlotsForDay($id, $day, $slotMinutes);

        return response()->json([
            'resource_id' => $model->id,
            'date' => $day->toDateString(),
            'slot_minutes' => $slotMinutes,
            'slots' => $slots,
        ]);
    }
}
