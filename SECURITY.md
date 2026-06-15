# Security Policy

## Supported versions

This is a **portfolio / reference** project, not a production SaaS. Security fixes are applied on a best-effort basis on the `main` branch.

| Version | Supported |
| ------- | --------- |
| latest `main` | yes |

## Reporting a vulnerability

If you discover a security issue, please **do not** open a public GitHub issue with exploit details.

1. Open a private security advisory on GitHub (preferred), or
2. Contact the maintainer via the email listed on their GitHub profile.

## Scope notes

- **Synthetic data only** — seeded users use `@example.com` and documented demo passwords.
- **Never commit** `.env`, JWT secrets, database passwords, or `auth.json`.
- **Docker Compose** ships local-only credentials (`booking` / `root`). Override `JWT_SECRET` and database passwords before any non-local deployment.
- **JWT** — access tokens are short-lived; refresh is enabled for API clients. This sample does not implement token rotation hardening or device binding.
- **Rate limiting** — auth and API routes are throttled (see `config/booking.php`).

## Recommended hardening for real deployments

Not implemented here by design (portfolio scope):

- HTTPS termination and HSTS
- Row-level locking or unique constraints for booking overlap under high concurrency
- Centralized secrets management
- WAF / API gateway
- Audit logging to external SIEM
