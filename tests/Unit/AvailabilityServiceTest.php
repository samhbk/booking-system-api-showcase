<?php

namespace Tests\Unit;

use App\Services\AvailabilityCacheService;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Mockery;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_suggest_slots_marks_overlapping_windows_unavailable(): void
    {
        $day = Carbon::parse('2026-06-15')->startOfDay();
        $cache = Mockery::mock(AvailabilityCacheService::class);
        $cache->shouldReceive('getBookedWindowsForDay')
            ->once()
            ->with(1, Mockery::type(Carbon::class))
            ->andReturn([
                [
                    'starts_at' => $day->copy()->setTime(10, 0)->toIso8601String(),
                    'ends_at' => $day->copy()->setTime(11, 0)->toIso8601String(),
                ],
            ]);

        $service = new AvailabilityService($cache);
        $slots = $service->suggestSlotsForDay(1, $day, 60);

        $tenAm = collect($slots)->first(fn (array $slot) => str_contains($slot['start'], 'T10:00:00'));
        $this->assertNotNull($tenAm);
        $this->assertFalse($tenAm['available']);

        $nineAm = collect($slots)->first(fn (array $slot) => str_contains($slot['start'], 'T09:00:00'));
        $this->assertNotNull($nineAm);
        $this->assertTrue($nineAm['available']);
    }
}
