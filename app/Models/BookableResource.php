<?php

namespace App\Models;

use Database\Factories\BookableResourceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'timezone',
    'slot_duration_minutes',
    'capacity',
    'is_active',
])]
class BookableResource extends Model
{
    /** @use HasFactory<BookableResourceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'capacity' => 'integer',
            'slot_duration_minutes' => 'integer',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'bookable_resource_id');
    }
}
