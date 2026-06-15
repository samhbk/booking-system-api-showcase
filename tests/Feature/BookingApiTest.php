<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_booking_returns_conflict(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(11, 0),
        ]);
        $token = JWTAuth::fromUser($user);
        $response = $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDay()->setTime(10, 30)->toIso8601String(),
            'ends_at' => now()->addDay()->setTime(11, 30)->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token]);
        $response->assertStatus(409)
            ->assertJsonPath('error', 'BookingConflictException');
    }

    public function test_user_can_create_non_overlapping_booking(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $token = JWTAuth::fromUser($user);
        $start = now()->addDays(2)->setTime(14, 0);
        $end = $start->copy()->addHour();
        $response = $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token]);
        $response->assertCreated()->assertJsonStructure([
            'data' => ['id', 'starts_at', 'ends_at', 'status'],
        ]);
    }

    public function test_booking_duration_must_align_with_resource_slot(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create([
            'is_active' => true,
            'slot_duration_minutes' => 60,
        ]);
        $token = JWTAuth::fromUser($user);
        $start = now()->addDays(4)->setTime(9, 0);
        $end = $start->copy()->addMinutes(45);
        $response = $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token]);
        $response->assertStatus(422)
            ->assertJsonPath('error', 'DomainException');
    }

    public function test_user_can_rebook_after_cancel(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDays(5)->setTime(12, 0),
            'ends_at' => now()->addDays(5)->setTime(13, 0),
            'status' => BookingStatus::Confirmed,
        ]);
        $token = JWTAuth::fromUser($user);

        $this->postJson('/api/v1/bookings/'.$booking->id.'/cancel', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $start = $booking->starts_at;
        $end = $booking->ends_at;
        $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])
            ->assertCreated();
    }

    public function test_user_cannot_cancel_another_users_booking(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDays(6)->setTime(8, 0),
            'ends_at' => now()->addDays(6)->setTime(9, 0),
        ]);
        $token = JWTAuth::fromUser($other);

        $this->postJson('/api/v1/bookings/'.$booking->id.'/cancel', [], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(403);
    }

    public function test_admin_can_cancel_any_booking(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true]);
        $booking = Booking::factory()->create([
            'user_id' => $owner->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDays(7)->setTime(15, 0),
            'ends_at' => now()->addDays(7)->setTime(16, 0),
        ]);
        $token = JWTAuth::fromUser($admin);

        $this->postJson('/api/v1/bookings/'.$booking->id.'/cancel', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_suggested_slots_endpoint_returns_grid(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $token = JWTAuth::fromUser($user);
        $date = now()->addDays(8)->toDateString();

        $response = $this->getJson(
            '/api/v1/resources/'.$resource->id.'/suggested-slots?date='.$date,
            ['Authorization' => 'Bearer '.$token]
        );
        $response->assertOk()
            ->assertJsonPath('resource_id', $resource->id)
            ->assertJsonStructure(['slots' => [['start', 'end', 'available']]]);
    }

    public function test_create_booking_dispatches_lifecycle_event(): void
    {
        Event::fake([BookingCreated::class]);

        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $token = JWTAuth::fromUser($user);
        $start = now()->addDays(9)->setTime(10, 0);
        $end = $start->copy()->addHour();

        $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])->assertCreated();

        Event::assertDispatched(BookingCreated::class);
    }

    public function test_create_booking_validation_fails_for_missing_fields(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->postJson('/api/v1/bookings', [], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['bookable_resource_id', 'starts_at', 'ends_at']);
    }

    public function test_cannot_book_inactive_resource(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => false, 'slot_duration_minutes' => 60]);
        $token = JWTAuth::fromUser($user);
        $start = now()->addDays(10)->setTime(11, 0);
        $end = $start->copy()->addHour();

        $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(404)
            ->assertJsonPath('error', 'ModelNotFoundDomainException');
    }

    public function test_user_can_reschedule_own_booking(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDays(11)->setTime(9, 0),
            'ends_at' => now()->addDays(11)->setTime(10, 0),
            'status' => BookingStatus::Confirmed,
        ]);
        $token = JWTAuth::fromUser($user);
        $newStart = now()->addDays(11)->setTime(14, 0);
        $newEnd = $newStart->copy()->addHour();

        $this->putJson('/api/v1/bookings/'.$booking->id, [
            'starts_at' => $newStart->toIso8601String(),
            'ends_at' => $newEnd->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.starts_at', $newStart->toIso8601String());
    }

    public function test_cannot_update_cancelled_booking(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => now()->addDays(12)->setTime(9, 0),
            'ends_at' => now()->addDays(12)->setTime(10, 0),
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
        $token = JWTAuth::fromUser($user);

        $this->putJson('/api/v1/bookings/'.$booking->id, [
            'starts_at' => now()->addDays(12)->setTime(15, 0)->toIso8601String(),
            'ends_at' => now()->addDays(12)->setTime(16, 0)->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(422)
            ->assertJsonPath('error', 'DomainException');
    }

    public function test_reschedule_into_conflict_returns_conflict(): void
    {
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $day = now()->addDays(13);
        Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => $day->copy()->setTime(14, 0),
            'ends_at' => $day->copy()->setTime(15, 0),
        ]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
            'starts_at' => $day->copy()->setTime(9, 0),
            'ends_at' => $day->copy()->setTime(10, 0),
        ]);
        $token = JWTAuth::fromUser($user);

        $this->putJson('/api/v1/bookings/'.$booking->id, [
            'starts_at' => $day->copy()->setTime(14, 30)->toIso8601String(),
            'ends_at' => $day->copy()->setTime(15, 30)->toIso8601String(),
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(409)
            ->assertJsonPath('error', 'BookingConflictException');
    }

    public function test_create_booking_requires_authentication(): void
    {
        $resource = BookableResource::factory()->create(['is_active' => true, 'slot_duration_minutes' => 60]);
        $start = now()->addDays(14)->setTime(10, 0);
        $end = $start->copy()->addHour();

        $this->postJson('/api/v1/bookings', [
            'bookable_resource_id' => $resource->id,
            'starts_at' => $start->toIso8601String(),
            'ends_at' => $end->toIso8601String(),
        ])->assertUnauthorized();
    }

    public function test_user_sees_only_own_bookings_in_index(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $resource = BookableResource::factory()->create(['is_active' => true]);
        $mine = Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
        ]);
        Booking::factory()->create([
            'user_id' => $other->id,
            'bookable_resource_id' => $resource->id,
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->getJson('/api/v1/bookings', ['Authorization' => 'Bearer '.$token]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($mine->id));
        $this->assertCount(1, $ids);
    }
}
