<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BookingRepository implements BookingRepositoryInterface
{
    public function findById(int $id): ?Booking
    {
        return Booking::query()->find($id);
    }

    public function create(array $attributes): Booking
    {
        return Booking::query()->create($attributes);
    }

    public function save(Booking $booking): Booking
    {
        $booking->save();

        return $booking->refresh();
    }

    public function paginateForScope(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = Booking::query()->with(['resource', 'user'])->orderByDesc('starts_at');
        if (! empty($filters['user_id'])) {
            $q->where('user_id', (int) $filters['user_id']);
        }
        if (! empty($filters['resource_id'])) {
            $q->where('bookable_resource_id', (int) $filters['resource_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['from'])) {
            $q->where('ends_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $q->where('starts_at', '<=', $filters['to']);
        }

        return $q->paginate($perPage);
    }

    public function hasOverlappingActiveBooking(
        int $resourceId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?int $exceptBookingId = null,
    ): bool {
        $q = Booking::query()->where('bookable_resource_id', $resourceId)
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);
        if ($exceptBookingId !== null) {
            $q->where('id', '!=', $exceptBookingId);
        }

        return $q->exists();
    }

    public function activeBetween(int $resourceId, CarbonInterface $rangeStart, CarbonInterface $rangeEnd): Collection
    {
        return Booking::query()
            ->where('bookable_resource_id', $resourceId)
            ->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->where('starts_at', '<', $rangeEnd)
            ->where('ends_at', '>', $rangeStart)
            ->orderBy('starts_at')
            ->get();
    }
}
