<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingLifecycleMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public string $action,
    ) {
        $this->onQueue('notifications');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Booking '.ucfirst($this->action).' — '.$this->booking->resource?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-lifecycle',
            with: [
                'booking' => $this->booking,
                'action' => $this->action,
            ],
        );
    }
}
