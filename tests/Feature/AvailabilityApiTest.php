<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AvailabilityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_returns_booked_windows_for_day(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true]);
        $day = now()->addDays(3)->startOfDay();
        Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => $day->copy()->setTime(10, 0),
            'ends_at' => $day->copy()->setTime(11, 0),
            'status' => BookingStatus::Confirmed,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson(
            '/api/v1/resources/'.$resource->id.'/availability?date='.$day->toDateString(),
            ['Authorization' => 'Bearer '.$token]
        );

        $response->assertOk()
            ->assertJsonPath('resource_id', $resource->id)
            ->assertJsonCount(1, 'booked');
    }

    public function test_availability_returns_404_for_inactive_resource(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => false]);
        $token = JWTAuth::fromUser($user);
        $date = now()->addDay()->toDateString();

        $this->getJson(
            '/api/v1/resources/'.$resource->id.'/availability?date='.$date,
            ['Authorization' => 'Bearer '.$token]
        )->assertNotFound();
    }

    public function test_availability_requires_date_parameter(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true]);
        $token = JWTAuth::fromUser($user);

        $this->getJson(
            '/api/v1/resources/'.$resource->id.'/availability',
            ['Authorization' => 'Bearer '.$token]
        )->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }
}
