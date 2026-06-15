<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminBookingIndexRequest;
use App\Http\Resources\BookingResource;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin — Bookings')]
class AdminBookingController extends Controller
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookingService $bookingService,
    ) {}

    #[OA\Get(path: '/api/v1/admin/bookings', security: [['bearerAuth' => []]], tags: ['Admin — Bookings'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function index(AdminBookingIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        unset($validated['per_page']);

        $scope = [
            'resource_id' => $validated['resource_id'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
            'status' => $validated['status'] ?? null,
            'from' => isset($validated['from']) ? Carbon::parse($validated['from']) : null,
            'to' => isset($validated['to']) ? Carbon::parse($validated['to']) : null,
            'q' => $validated['q'] ?? null,
        ];

        $paginator = $this->bookings->paginateForScope($scope, $perPage);

        return BookingResource::collection($paginator)
            ->additional(['meta' => ['pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ]]])
            ->response();
    }

    #[OA\Post(path: '/api/v1/admin/bookings/{booking}/cancel', security: [['bearerAuth' => []]], tags: ['Admin — Bookings'])]
    #[OA\Response(response: 200, description: 'OK')]
    public function cancel(Request $request, int $booking): JsonResponse
    {
        $model = $this->bookingService->cancel($request->user(), $booking);

        return (new BookingResource($model))->response();
    }
}
