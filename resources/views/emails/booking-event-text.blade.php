Booking {{ $eventType }}

ID: {{ $booking->id }}
Bookable resource ID: {{ $booking->bookable_resource_id }}
Starts: {{ $booking->starts_at }}
Ends: {{ $booking->ends_at }}
Status: {{ $booking->status->value }}
