<?php

use App\Http\Controllers\Api\V1\Admin\AdminBookingController;
use App\Http\Controllers\Api\V1\Admin\AdminResourceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ResourceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:auth'])->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::post('auth/refresh', [AuthController::class, 'refresh'])
    ->middleware(['jwt.refresh']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    Route::get('resources', [ResourceController::class, 'index']);
    Route::get('resources/{resource}', [ResourceController::class, 'show'])->whereNumber('resource');

    Route::get('resources/{id}/availability', [AvailabilityController::class, 'day'])->whereNumber('id');
    Route::get('resources/{id}/suggested-slots', [AvailabilityController::class, 'slots'])->whereNumber('id');

    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::put('bookings/{id}', [BookingController::class, 'update'])->whereNumber('id');
    Route::post('bookings/{id}/cancel', [BookingController::class, 'cancel'])->whereNumber('id');

    Route::middleware(['role:admin'])->prefix('admin')->group(function () {
        Route::get('bookings', [AdminBookingController::class, 'index']);
        Route::post('bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])->whereNumber('booking');

        Route::post('resources', [AdminResourceController::class, 'store']);
        Route::patch('resources/{resource}', [AdminResourceController::class, 'update'])->whereNumber('resource');
    });
});
