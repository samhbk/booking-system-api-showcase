<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Mail\BookingLifecycleMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingLifecycleNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(BookingCreated|BookingUpdated|BookingCancelled $event): void
    {
        $booking = $event->booking;
        $action = match (true) {
            $event instanceof BookingCreated => 'created',
            $event instanceof BookingUpdated => 'updated',
            $event instanceof BookingCancelled => 'cancelled',
            default => 'updated',
        };

        $booking->loadMissing(['user', 'resource']);
        $user = $booking->user;
        if (! $user?->email) {
            Log::channel('booking')->warning('booking.notification.skipped', ['booking_id' => $booking->id]);

            return;
        }

        Log::channel('booking')->info('booking.notification', [
            'booking_id' => $booking->id,
            'event' => $action,
            'user_id' => $booking->user_id,
        ]);

        Mail::to($user->email)->queue(new BookingLifecycleMail($booking, $action));
    }
}
