<x-mail::message>
# Booking {{ ucfirst($action) }}

**Resource:** {{ $booking->resource?->name ?? 'N/A' }}  
**Starts:** {{ $booking->starts_at->toIso8601String() }}  
**Ends:** {{ $booking->ends_at->toIso8601String() }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
