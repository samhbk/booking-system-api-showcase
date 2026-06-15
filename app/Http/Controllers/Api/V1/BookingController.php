<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\IndexBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Bookings')]
class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    #[OA\Get(path: '/api/v1/bookings', security: [['bearerAuth' => []]], tags: ['Bookings'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function index(IndexBookingRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $filters = [
            'resource_id' => isset($validated['resource_id']) ? (int) $validated['resource_id'] : null,
            'status' => $validated['status'] ?? null,
            'from' => ! empty($validated['from']) ? Carbon::parse($validated['from']) : null,
            'to' => ! empty($validated['to']) ? Carbon::parse($validated['to']) : null,
            'q' => $validated['q'] ?? null,
        ];

        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 100);

        if (! $user->isAdmin()) {
            $filters['user_id'] = $user->id;
        } elseif (! empty($validated['user_id'])) {
            $filters['user_id'] = (int) $validated['user_id'];
        }

        $paginator = $this->bookings->paginateForScope($filters, $perPage);

        return BookingResource::collection($paginator)->response();
    }

    #[OA\Post(path: '/api/v1/bookings', security: [['bearerAuth' => []]], tags: ['Bookings'])]
    #[OA\Response(response: 201, description: 'Created')]
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $booking = $this->bookingService->create(
            $request->user(),
            (int) $data['bookable_resource_id'],
            Carbon::parse($data['starts_at']),
            Carbon::parse($data['ends_at']),
            $data['notes'] ?? null,
        );

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(path: '/api/v1/bookings/{id}', security: [['bearerAuth' => []]], tags: ['Bookings'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function update(UpdateBookingRequest $request, int $id): JsonResponse
    {
        $existing = $this->bookings->findById($id);
        if (! $existing) {
            abort(404);
        }
        $validated = $request->validated();
        $starts = isset($validated['starts_at']) ? Carbon::parse($validated['starts_at']) : $existing->starts_at;
        $ends = isset($validated['ends_at']) ? Carbon::parse($validated['ends_at']) : $existing->ends_at;
        $includeNotes = array_key_exists('notes', $validated);
        $notes = $includeNotes ? ($validated['notes'] ?? null) : null;

        $booking = $this->bookingService->updateWindow(
            $request->user(),
            $id,
            $starts,
            $ends,
            $notes,
            $includeNotes,
        );

        return (new BookingResource($booking))->response();
    }

    #[OA\Post(path: '/api/v1/bookings/{id}/cancel', security: [['bearerAuth' => []]], tags: ['Bookings'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = $this->bookingService->cancel($request->user(), $id);

        return (new BookingResource($booking))->response();
    }
}
