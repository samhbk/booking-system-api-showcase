# API versioning

The public HTTP API is versioned under the `/api/v1` prefix. Version **1** is the current stable surface for this showcase.

## URL structure

```
/api/v1/{resource}
```

Examples:

| Route file | Mounted at |
|------------|------------|
| `routes/api.php` | Laravel `api` prefix → `/api` |
| `routes/api_v1.php` | `Route::prefix('v1')` → `/api/v1` |

```php
// routes/api.php
Route::prefix('v1')->group(function () {
    require base_path('routes/api_v1.php');
});
```

All v1 endpoints are defined in `routes/api_v1.php`. Controllers live under `App\Http\Controllers\Api\V1` (including `Admin/`).

## What is versioned

| Layer | Convention |
|-------|------------|
| URL path | `/api/v1/...` |
| Controllers | `Api\V1\*` namespace |
| Form requests | Shared across versions until a breaking change needs `V2` copies |
| OpenAPI | Generated spec describes v1 paths only |
| JSON resources | Stable field names within v1 |

## What is not versioned separately

- **Database schema** — single migration set; v2 would share tables unless a breaking redesign requires new columns with compatibility shims
- **JWT claims** — same `auth:api` guard for all API versions
- **Internal services** — `BookingService`, repositories, etc. are version-agnostic; controllers adapt HTTP ↔ domain

## Adding v2 (future pattern)

When breaking changes are needed (renamed fields, different error shape, removed endpoints):

1. Create `routes/api_v2.php` and `App\Http\Controllers\Api\V2\…`
2. Register in `routes/api.php`:

   ```php
   Route::prefix('v2')->group(function () {
       require base_path('routes/api_v2.php');
   });
   ```

3. Keep v1 routes until clients migrate
4. Document deprecation timeline in README and OpenAPI `info.version`
5. Run parallel test suites: `tests/Feature/V1/*`, `tests/Feature/V2/*`

**Non-breaking changes** (new optional JSON fields, new endpoints) stay in v1.

## Compatibility guarantees (v1)

Within v1, this repo aims for:

- Stable endpoint paths and HTTP methods
- Consistent error JSON: `{ "message", "error" }` for domain errors
- ISO 8601 timestamps in responses
- Pagination shape: `{ "data", "meta": { "pagination": … } }` where applicable

## OpenAPI & Swagger

- Spec generation: `php artisan l5-swagger:generate`
- UI: `/api/documentation`
- Annotations: `app/OpenApi/ApiDoc.php` + controller docblocks

The generated document reflects the v1 base path (`/api/v1`).

## Client guidance

```http
# Always pin the major version in the path
GET https://api.example.com/api/v1/resources
Authorization: Bearer <access_token>
```

Do not rely on unversioned `/api/*` routes — none are exposed for domain resources.

## Related

- [API reference](API.md)
- [Booking workflow](BOOKING_WORKFLOW.md)
