<?php

namespace Tests\Feature;

use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_all_bookings(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $resource = BookableResource::factory()->create();
        Booking::factory()->create([
            'user_id' => $user->id,
            'bookable_resource_id' => $resource->id,
        ]);
        $token = JWTAuth::fromUser($admin);

        $this->getJson('/api/v1/admin/bookings', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['pagination']]);
    }

    public function test_non_admin_cannot_access_admin_bookings(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->getJson('/api/v1/admin/bookings', ['Authorization' => 'Bearer '.$token])
            ->assertForbidden();
    }

    public function test_admin_can_create_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $token = JWTAuth::fromUser($admin);

        $this->postJson('/api/v1/admin/resources', [
            'name' => 'Studio B',
            'slug' => 'studio-b',
            'description' => 'Quiet workspace',
            'capacity' => 4,
            'slot_duration_minutes' => 30,
            'is_active' => true,
            'timezone' => 'Europe/Berlin',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'studio-b');
    }

    public function test_non_admin_cannot_create_resource(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->postJson('/api/v1/admin/resources', [
            'name' => 'Studio B',
            'slug' => 'studio-b',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertForbidden();
    }

    public function test_admin_can_update_resource(): void
    {
        $admin = User::factory()->admin()->create();
        $resource = BookableResource::factory()->create(['name' => 'Old Name']);
        $token = JWTAuth::fromUser($admin);

        $this->patchJson('/api/v1/admin/resources/'.$resource->id, [
            'name' => 'Updated Name',
        ], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');
    }
}
