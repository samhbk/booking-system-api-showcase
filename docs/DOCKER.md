# Docker setup

Run the full stack (Laravel app, MySQL 8, Redis 7) without installing PHP or extensions locally.

## Quick start

```bash
cp .env.example .env
docker compose up --build
```

| URL | Purpose |
|-----|---------|
| http://localhost:8000/api/v1 | API base |
| http://localhost:8000/api/documentation | Swagger UI |
| http://localhost:8000/up | Health check |

On first start the `app` service:

1. Runs `composer install`
2. Generates `APP_KEY` and `JWT_SECRET` if missing
3. Runs migrations and seeders
4. Serves on `0.0.0.0:8000`

## Services

| Service | Image | Port |
|---------|-------|------|
| `app` | Built from `Dockerfile` (PHP 8.3 CLI Alpine) | 8000 |
| `mysql` | `mysql:8.0` | 3306 |
| `redis` | `redis:7-alpine` | 6379 |

The app container bind-mounts the project directory for local development.

## Environment (Compose)

`docker-compose.yml` overrides key variables for the container network:

| Variable | Value in Compose |
|----------|------------------|
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `mysql` |
| `CACHE_STORE` | `redis` |
| `REDIS_HOST` | `redis` |
| `BOOKING_AVAILABILITY_CACHE_STORE` | `redis` |
| `JWT_SECRET` | `${JWT_SECRET:-change-me-in-production-use-php-artisan-jwt-secret}` |

Set `JWT_SECRET` in your shell or `.env` before `docker compose up` for a stable secret across restarts:

```bash
export JWT_SECRET="$(openssl rand -base64 32)"
docker compose up --build
```

## Queues & mail

- **Queue driver:** `database` — lifecycle emails are queued
- **Mail driver:** `log` (from `.env.example`)

Process jobs in a second terminal:

```bash
docker compose exec app php artisan queue:work
```

## Production image (smoke test)

CI builds a slim image without dev dependencies:

```bash
docker build --build-arg INSTALL_DEV=false -t booking-api-showcase:prod .
```

Run without Compose (requires external MySQL/Redis and env vars):

```bash
docker run --rm -p 8000:8000 \
  -e APP_KEY=base64:... \
  -e JWT_SECRET=... \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  booking-api-showcase:prod
```

For real deployments, use a proper PHP-FPM + nginx image, secrets management, and HTTPS — this Dockerfile targets **local demo and CI smoke builds**.

## Troubleshooting

| Issue | Fix |
|-------|-----|
| `Connection refused` to MySQL | Wait for `mysql` healthcheck; restart `app` |
| 401 on all routes after restart | Tokens signed with old `JWT_SECRET` — log in again |
| Port 8000 in use | Change `ports` mapping in `docker-compose.yml` |
| Permission errors on `storage/` | `docker compose exec app chmod -R ug+rwx storage bootstrap/cache` |

## Teardown

```bash
docker compose down        # stop containers
docker compose down -v     # also remove mysql_data volume
```
