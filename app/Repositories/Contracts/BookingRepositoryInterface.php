<?php

namespace App\Repositories\Contracts;

use App\Models\Booking;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BookingRepositoryInterface
{
    public function findById(int $id): ?Booking;

    public function create(array $attributes): Booking;

    public function save(Booking $booking): Booking;

    public function paginateForScope(array $filters, int $perPage = 15): LengthAwarePaginator;

    public function hasOverlappingActiveBooking(
        int $resourceId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $exceptBookingId = null,
    ): bool;

    /**
     * Active (pending/confirmed) bookings overlapping [rangeStart, rangeEnd).
     *
     * @return Collection<int, Booking>
     */
    public function activeBetween(int $resourceId, CarbonInterface $rangeStart, CarbonInterface $rangeEnd): Collection;
}
