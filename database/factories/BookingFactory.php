<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $starts = fake()->dateTimeBetween('+1 day', '+2 weeks');

        return [
            'bookable_resource_id' => BookableResource::factory(),
            'user_id' => User::factory(),
            'starts_at' => $starts,
            'ends_at' => (clone $starts)->modify('+1 hour'),
            'status' => BookingStatus::Confirmed,
            'notes' => null,
        ];
    }
}
