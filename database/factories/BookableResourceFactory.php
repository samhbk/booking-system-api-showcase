<?php

namespace Database\Factories;

use App\Models\BookableResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BookableResource>
 */
class BookableResourceFactory extends Factory
{
    protected $model = BookableResource::class;

    public function definition(): array
    {
        $name = fake()->words(3, true).' Room';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
            'timezone' => 'UTC',
            'slot_duration_minutes' => 60,
            'capacity' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}
