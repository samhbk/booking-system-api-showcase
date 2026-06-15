# Concurrency & overlap handling

This API prevents double-booking of the same resource window using **transaction-scoped overlap checks** and **indexed range queries**. It is designed for portfolio clarity and moderate traffic — not for extreme contention without additional hardening.

## Overlap model

Two intervals `[A_start, A_end)` and `[B_start, B_end)` overlap when:

```
A_start < B_end  AND  A_end > B_start
```

Only bookings with status `pending` or `confirmed` participate. `cancelled` bookings free their slot immediately.

## Where checks run

All mutating booking operations wrap overlap detection in a **database transaction**:

```php
// BookingService::create() — simplified
return DB::transaction(function () use (...) {
    if ($this->bookings->hasOverlappingActiveBooking($resourceId, $startsAt, $endsAt)) {
        throw new BookingConflictException; // HTTP 409
    }
    // INSERT ...
});
```

The same pattern applies to `updateWindow()` (reschedule), excluding the current booking ID from the query.

## Overlap SQL

`BookingRepository::hasOverlappingActiveBooking()`:

```php
Booking::query()
    ->where('bookable_resource_id', $resourceId)
    ->whereIn('status', ['pending', 'confirmed'])
    ->where('starts_at', '<', $endsAt)
    ->where('ends_at', '>', $startsAt)
    ->when($exceptBookingId, fn ($q) => $q->where('id', '!=', $exceptBookingId))
    ->exists();
```

This is the standard half-open interval overlap predicate. It catches partial overlaps, containment, and exact duplicates.

## Database indexes

Migration `2026_03_21_000000_create_booking_domain_tables` adds:

```
bookings_resource_status_window_idx (bookable_resource_id, status, starts_at, ends_at)
bookings_user_status_starts_idx    (user_id, status, starts_at)
```

These support fast per-resource overlap scans filtered by active statuses.

## Transaction boundaries

| Operation | Transaction | Cache invalidation |
|-----------|-------------|-------------------|
| Create | Yes | After insert |
| Reschedule | Yes | Old + new date ranges |
| Cancel | Yes | Booking window |

Cache invalidation runs inside the same transaction callback (before commit). A failed insert rolls back the booking; cache keys are only cleared after a successful write in the current implementation.

## Race conditions (honest scope)

Under **concurrent requests** for the same slot, two transactions can both pass `exists()` before either commits. This is a known **check-then-act** race.

**What this repo does:**

- Serializable isolation is **not** enabled by default
- No `SELECT … FOR UPDATE` row locks on the resource
- No database-level exclusion constraint (e.g. PostgreSQL `tstzrange` + GiST)

**What would harden production:**

1. **Pessimistic lock** — `BookableResource::lockForUpdate()` at the start of the transaction
2. **Unique constraint** — DB-enforced non-overlap (PostgreSQL ranges or generated slot columns)
3. **Optimistic versioning** — version column on resource or booking aggregate
4. **Idempotency keys** — client-supplied key deduplicated at the API layer

The README lists these as intentional future improvements. For portfolio review, the current design shows **awareness** of the problem and a **correct single-threaded path** with tests for **409 conflicts** when overlap already exists.

## Cache consistency

Availability reads use Redis (or array in tests) via `AvailabilityCacheService`:

- **Key pattern:** `booking:avail:day:{resourceId}:{Y-m-d}`
- **TTL:** `BOOKING_AVAILABILITY_CACHE_TTL` (default 120s)
- **Invalidation:** `forgetDaysForRange()` on every booking write touching that resource

Stale cache cannot cause a successful double-book — overlap is always re-checked in the transaction against the database. Stale cache at worst shows outdated availability until TTL or invalidation.

## API responses

| Situation | HTTP | `error` field |
|-----------|------|---------------|
| Overlap on create/reschedule | **409** | `BookingConflictException` |
| Invalid window (end ≤ start, wrong slot size) | **422** | `DomainException` |
| Inactive resource | **404** | `ModelNotFoundDomainException` |

## Tests

| Test | File |
|------|------|
| Overlap on create → 409 | `BookingApiTest::test_overlapping_booking_returns_conflict` |
| Rebook after cancel → 201 | `BookingApiTest::test_user_can_rebook_after_cancel` |
| Reschedule overlap → 409 | `BookingApiTest::test_reschedule_into_conflict_returns_conflict` |
| Slot grid marks booked windows | `AvailabilityServiceTest` |
