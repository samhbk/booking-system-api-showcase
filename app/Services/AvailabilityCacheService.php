<?php

namespace App\Services;

use App\Models\Booking;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

class AvailabilityCacheService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    public function getBookedWindowsForDay(int $resourceId, CarbonInterface $day): array
    {
        $store = config('booking.availability_cache_store');
        $ttl = (int) config('booking.availability_cache_ttl', 120);
        $key = sprintf('booking:avail:day:%d:%s', $resourceId, $day->toDateString());

        try {
            $cache = Cache::store($store);
        } catch (\Throwable) {
            $cache = Cache::store();
        }

        return $cache->remember($key, $ttl, function () use ($resourceId, $day) {
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();

            return $this->bookings->activeBetween($resourceId, $start, $end)
                ->map(fn (Booking $b) => [
                    'starts_at' => $b->starts_at->toIso8601String(),
                    'ends_at' => $b->ends_at->toIso8601String(),
                ])
                ->all();
        });
    }

    public function forgetDaysForRange(int $resourceId, CarbonInterface $from, CarbonInterface $to): void
    {
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        $store = config('booking.availability_cache_store');

        while ($cursor->lte($end)) {
            $key = sprintf('booking:avail:day:%d:%s', $resourceId, $cursor->toDateString());
            try {
                Cache::store($store)->forget($key);
            } catch (\Throwable) {
                Cache::store()->forget($key);
            }
            $cursor->addDay();
        }
    }
}
