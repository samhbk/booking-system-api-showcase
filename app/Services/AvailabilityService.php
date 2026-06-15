<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Read-side availability: booked windows (cached) and deterministic slot suggestions.
 */
class AvailabilityService
{
    public function __construct(
        private readonly AvailabilityCacheService $availabilityCache,
    ) {}

    /**
     * @return list<array{starts_at: string, ends_at: string}>
     */
    public function bookedWindowsForDay(int $bookableResourceId, CarbonInterface $day): array
    {
        return $this->availabilityCache->getBookedWindowsForDay($bookableResourceId, $day);
    }

    /**
     * @return list<array{start: string, end: string, available: bool}>
     */
    public function suggestSlotsForDay(int $bookableResourceId, CarbonInterface $day, int $slotMinutes): array
    {
        $slotMinutes = max(1, min(24 * 60, $slotMinutes));
        $day = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        $booked = $this->availabilityCache->getBookedWindowsForDay($bookableResourceId, $day);
        $bookedRanges = array_map(static function (array $w): array {
            return [
                Carbon::parse($w['starts_at']),
                Carbon::parse($w['ends_at']),
            ];
        }, $booked);

        $slots = [];
        $cursor = $day->copy();

        while ($cursor->copy()->addMinutes($slotMinutes)->lte($dayEnd)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($slotMinutes);

            $available = true;
            foreach ($bookedRanges as [$bStart, $bEnd]) {
                if ($slotStart->lt($bEnd) && $slotEnd->gt($bStart)) {
                    $available = false;
                    break;
                }
            }

            $slots[] = [
                'start' => $slotStart->toIso8601String(),
                'end' => $slotEnd->toIso8601String(),
                'available' => $available,
            ];

            $cursor->addMinutes($slotMinutes);
        }

        return $slots;
    }
}
