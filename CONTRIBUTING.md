# Contributing

Thank you for your interest in this project. This repository is primarily a **portfolio / reference** Laravel API, but issues and pull requests that improve clarity, tests, or documentation are welcome.

## Before you start

1. Search existing [issues](https://github.com/sameh-bakleh/booking-system-api-showcase/issues) to avoid duplicates.
2. For large changes, open an issue first to discuss scope.
3. Keep pull requests **focused** — one concern per PR when possible.

## Local setup

```bash
git clone https://github.com/sameh-bakleh/booking-system-api-showcase.git
cd booking-system-api-showcase
cp .env.example .env
composer install
php artisan key:generate
php artisan jwt:secret
touch database/database.sqlite   # default SQLite dev setup
php artisan migrate --seed
```

Docker alternative: `docker compose up --build` (see README).

## Development workflow

```bash
# Run tests
composer test

# Check code style (same as CI)
vendor/bin/pint --test

# Auto-fix style locally
vendor/bin/pint

# Regenerate OpenAPI
php artisan l5-swagger:generate
```

## Pull request checklist

- [ ] Tests pass locally (`composer test`)
- [ ] Pint passes (`vendor/bin/pint --test`)
- [ ] No secrets, `.env`, or `vendor/` committed
- [ ] README or `docs/API.md` updated if behavior or endpoints changed
- [ ] New features include PHPUnit coverage where practical

## Code conventions

- Follow existing layering: Controllers → Services → Repositories.
- Use Form Requests for HTTP validation.
- Domain rules belong in services, not controllers.
- Prefer stable JSON error shapes via `DomainException` subclasses.

## Security

Please do **not** file public issues for vulnerabilities. See [SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
