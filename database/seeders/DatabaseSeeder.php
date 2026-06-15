<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
            ]
        );

        $user = User::query()->updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
            ]
        );

        $roomA = BookableResource::query()->updateOrCreate(
            ['slug' => 'conference-room-a'],
            [
                'name' => 'Conference Room A',
                'description' => 'Seeded bookable resource for API demos (no real tenant data).',
                'capacity' => 10,
                'is_active' => true,
                'timezone' => 'Europe/Berlin',
                'slot_duration_minutes' => 60,
            ]
        );

        $roomB = BookableResource::query()->updateOrCreate(
            ['slug' => 'desk-pod-1'],
            [
                'name' => 'Desk Pod 1',
                'description' => 'Second demo resource with 30-minute alignment.',
                'capacity' => 4,
                'is_active' => true,
                'timezone' => 'Europe/Berlin',
                'slot_duration_minutes' => 30,
            ]
        );

        $day = now()->addDays(3)->startOfDay()->setHour(9);

        Booking::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'bookable_resource_id' => $roomA->id,
                'starts_at' => $day->copy()->setTime(10, 0),
            ],
            [
                'ends_at' => $day->copy()->setTime(11, 0),
                'status' => BookingStatus::Confirmed,
                'notes' => 'Seeded demo booking',
            ]
        );

        Booking::query()->updateOrCreate(
            [
                'user_id' => $admin->id,
                'bookable_resource_id' => $roomA->id,
                'starts_at' => $day->copy()->setTime(14, 0),
            ],
            [
                'ends_at' => $day->copy()->setTime(15, 0),
                'status' => BookingStatus::Confirmed,
                'notes' => 'Seeded admin booking',
            ]
        );

        Booking::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'bookable_resource_id' => $roomB->id,
                'starts_at' => $day->copy()->setTime(9, 0),
            ],
            [
                'ends_at' => $day->copy()->setTime(9, 30),
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now()->subHour(),
                'notes' => 'Cancelled sample — slot is free for a new booking',
            ]
        );

        $this->command?->info('Demo credentials (local / Docker only):');
        $this->command?->info('  Admin: admin@example.com / password');
        $this->command?->info('  User:  user@example.com / password');
    }
}
