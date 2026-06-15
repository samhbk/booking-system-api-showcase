<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Exceptions\BookingConflictException;
use App\Exceptions\DomainException;
use App\Exceptions\ModelNotFoundDomainException;
use App\Models\BookableResource;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\BookableResourceRepositoryInterface;
use App\Repositories\Contracts\BookingRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
        private readonly BookableResourceRepositoryInterface $resources,
        private readonly AvailabilityCacheService $availabilityCache,
        private readonly LoggerInterface $logger,
    ) {}

    public function create(
        User $user,
        int $resourceId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?string $notes = null,
        BookingStatus $status = BookingStatus::Confirmed,
    ): Booking {
        $resource = $this->resources->findById($resourceId);
        if (! $resource || ! $resource->is_active) {
            throw new ModelNotFoundDomainException('Bookable resource not found or inactive.');
        }

        if ($endsAt->lte($startsAt)) {
            throw new DomainException('End time must be after start time.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->assertDurationMatchesResourceSlot($resource, $startsAt, $endsAt);

        return DB::transaction(function () use ($user, $resourceId, $startsAt, $endsAt, $notes, $status) {
            if ($this->bookings->hasOverlappingActiveBooking($resourceId, $startsAt, $endsAt)) {
                throw new BookingConflictException;
            }

            $booking = $this->bookings->create([
                'user_id' => $user->id,
                'bookable_resource_id' => $resourceId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => $status,
                'notes' => $notes,
            ]);

            $this->availabilityCache->forgetDaysForRange($resourceId, $startsAt, $endsAt);
            $this->logger->info('booking.created', ['booking_id' => $booking->id, 'user_id' => $user->id]);

            BookingCreated::dispatch($booking);

            return $booking->load(['resource', 'user']);
        });
    }

    public function updateWindow(
        User $actor,
        int $bookingId,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?string $notes = null,
        bool $includeNotes = false,
    ): Booking {
        $booking = $this->bookings->findById($bookingId);
        if (! $booking) {
            throw new ModelNotFoundDomainException('Booking not found.');
        }

        $this->assertCanMutate($actor, $booking);

        if ($booking->isCancelled()) {
            throw new DomainException('Cancelled bookings cannot be updated.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($endsAt->lte($startsAt)) {
            throw new DomainException('End time must be after start time.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $resource = $this->resources->findById((int) $booking->bookable_resource_id);
        if ($resource) {
            $this->assertDurationMatchesResourceSlot($resource, $startsAt, $endsAt);
        }

        $oldStart = $booking->starts_at;
        $oldEnd = $booking->ends_at;

        return DB::transaction(function () use ($booking, $startsAt, $endsAt, $notes, $includeNotes, $oldStart, $oldEnd) {
            if ($this->bookings->hasOverlappingActiveBooking(
                $booking->bookable_resource_id,
                $startsAt,
                $endsAt,
                $booking->id,
            )) {
                throw new BookingConflictException;
            }

            $booking->starts_at = $startsAt;
            $booking->ends_at = $endsAt;
            if ($includeNotes) {
                $booking->notes = $notes;
            }
            $this->bookings->save($booking);

            $this->availabilityCache->forgetDaysForRange($booking->bookable_resource_id, $oldStart, $oldEnd);
            $this->availabilityCache->forgetDaysForRange($booking->bookable_resource_id, $startsAt, $endsAt);
            $this->logger->info('booking.updated', ['booking_id' => $booking->id]);

            BookingUpdated::dispatch($booking);

            return $booking->load(['resource', 'user']);
        });
    }

    public function cancel(User $actor, int $bookingId): Booking
    {
        $booking = $this->bookings->findById($bookingId);
        if (! $booking) {
            throw new ModelNotFoundDomainException('Booking not found.');
        }

        $this->assertCanMutate($actor, $booking);

        if ($booking->isCancelled()) {
            return $booking->load(['resource', 'user']);
        }

        return DB::transaction(function () use ($booking) {
            $booking->status = BookingStatus::Cancelled;
            $booking->cancelled_at = now();
            $this->bookings->save($booking);

            $this->availabilityCache->forgetDaysForRange(
                $booking->bookable_resource_id,
                $booking->starts_at,
                $booking->ends_at,
            );
            $this->logger->info('booking.cancelled', ['booking_id' => $booking->id]);

            BookingCancelled::dispatch($booking);

            return $booking->load(['resource', 'user']);
        });
    }

    private function assertCanMutate(User $actor, Booking $booking): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        if ((int) $booking->user_id !== (int) $actor->id) {
            throw new DomainException('Forbidden.', Response::HTTP_FORBIDDEN);
        }
    }

    private function assertDurationMatchesResourceSlot(
        BookableResource $resource,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
    ): void {
        $slotMinutes = max(1, (int) $resource->slot_duration_minutes);
        $duration = (int) $startsAt->diffInMinutes($endsAt);
        if ($duration <= 0) {
            return;
        }
        if ($duration % $slotMinutes !== 0) {
            throw new DomainException(
                "Booking length must be a multiple of the resource slot size ({$slotMinutes} minutes).",
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }
    }
}
