<?php

return [
    'default_slot_minutes' => (int) env('BOOKING_DEFAULT_SLOT_MINUTES', 60),
    'availability_cache_store' => env('BOOKING_AVAILABILITY_CACHE_STORE', env('CACHE_AVAILABILITY_STORE', 'redis')),
    'availability_cache_ttl' => (int) env('BOOKING_AVAILABILITY_CACHE_TTL', env('BOOKING_AVAILABILITY_TTL', 120)),
    'api_rate_per_minute' => (int) env('BOOKING_API_RATE_PER_MINUTE', 120),
    'auth_rate_per_minute' => (int) env('BOOKING_AUTH_RATE_PER_MINUTE', 10),
];
