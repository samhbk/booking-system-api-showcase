# API reference (v1)

Base URL: `/api/v1`

**Related docs:** [Booking workflow](BOOKING_WORKFLOW.md) · [Concurrency](CONCURRENCY.md) · [API versioning](API_VERSIONING.md)

All timestamps are ISO 8601. Authenticated requests use:

```http
Authorization: Bearer <access_token>
```

## Authentication

### POST `/auth/register`

Create a user (default role: `user`).

**Body**

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**201** — `data.access_token`, `data.user`

### POST `/auth/login`

**Body**

```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**200** — token payload | **401** — `error: invalid_credentials`

### POST `/auth/refresh`

Requires valid JWT (refresh middleware). **200** — new `access_token`.

### POST `/auth/logout`

Requires JWT. **200** — token invalidated.

### GET `/auth/me`

Requires JWT. **200** — current user resource.

---

## Bookable resources

### GET `/resources`

Query: `q`, `is_active`, `per_page` (max 100).

### GET `/resources/{id}`

**404** if missing.

### GET `/resources/{id}/availability`

Query: `date` (required, `Y-m-d`).

**200**

```json
{
  "resource_id": 1,
  "date": "2026-06-15",
  "booked": [
    { "start": "2026-06-15T10:00:00+00:00", "end": "2026-06-15T11:00:00+00:00" }
  ]
}
```

### GET `/resources/{id}/suggested-slots`

Query: `date` (required). Returns slot grid with `available` boolean per slot.

---

## Bookings

### GET `/bookings`

Users see only their bookings. Admins may pass `user_id`, `resource_id`, `status`, `from`, `to`, `per_page`.

### POST `/bookings`

**Body**

```json
{
  "bookable_resource_id": 1,
  "starts_at": "2026-06-20T10:00:00+00:00",
  "ends_at": "2026-06-20T11:00:00+00:00",
  "notes": "Optional"
}
```

| Status | Meaning |
|--------|---------|
| **201** | Created (`status: confirmed`) |
| **409** | Overlap — `error: BookingConflictException` |
| **422** | Validation or slot alignment — `error: DomainException` |
| **404** | Inactive or missing resource |

### PUT `/bookings/{id}`

Reschedule and/or update `notes`. Owner or admin only.

**422** if booking is cancelled.

### POST `/bookings/{id}/cancel`

Sets `status` to `cancelled` and `cancelled_at`. Owner or admin. **403** for other users.

---

## Admin

Requires JWT with `role: admin`.

### GET `/admin/bookings`

Filters: `user_id`, `resource_id`, `status`, `from`, `to`, `q`, `per_page`.

### POST `/admin/bookings/{id}/cancel`

Cancel any booking.

### POST `/admin/resources`

Create bookable resource (`name`, `slug`, `capacity`, `slot_duration_minutes`, `timezone`, `is_active`, …).

### PATCH `/admin/resources/{id}`

Partial update.

---

## Error shape

Domain errors (API routes):

```json
{
  "message": "Human-readable message",
  "error": "ExceptionClassName"
}
```

Validation errors (**422**):

```json
{
  "message": "The given data was invalid.",
  "errors": { "field": ["…"] }
}
```

---

## OpenAPI / Swagger

Generate spec:

```bash
php artisan l5-swagger:generate
```

Browse interactive docs at `/api/documentation`.
