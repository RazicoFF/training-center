# Training Center Backend

PHP + MySQL REST API for the training center Android app.

## Setup

1. `composer install`
2. Copy `.env.example` to `.env` and `.env.testing`, fill in DB credentials.
3. Generate a real `JWT_SECRET` for each env file (e.g. `php -r "echo bin2hex(random_bytes(32));"`).
   Never leave `JWT_SECRET` unset or at the `change-me-in-production` example value in
   `.env` — the server refuses to sign or verify tokens with it and every authenticated
   request will fail with a generic 500 until it is set.
4. Create databases: `training_center` and `training_center_test` (utf8mb4).
5. Run migrations: `php database/migrate.php .env` and `php database/migrate.php .env.testing`
6. Seed professions: `php database/seeders/seed.php .env` and `php database/seeders/seed.php .env.testing`
7. Serve locally: `php -S localhost:8080 -t public`

## Tests

`vendor/bin/phpunit`

## API

See `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the full endpoint list and DB schema.

## Known gaps

- **No admin approval HTTP endpoint yet.** `ApplicationRepository::approve()` implements
  application approval (creates the student user, links `applications.created_user_id`,
  sets `status = 'approved'`, transactionally) and is covered by unit tests, but no
  controller/route exposes it over HTTP yet. A future admin-facing endpoint should call it.
- **No certificate-issuance HTTP endpoint yet.** `CertificateRepository::issue()` is fully
  implemented and tested, but certificates can currently only be created programmatically
  (e.g. from a future admin/teacher flow), not via a public API route.
- **`composer.lock` is not committed.** PHP/Composer are not available in the environment
  this branch was developed in, so `composer.lock` could not be generated. It must be
  generated and committed on the first real `composer install` against `composer.json`,
  and kept in sync afterwards.
- **Apache + CGI/FastCGI may strip the `Authorization` header.** Under `mod_cgi`/`mod_fcgid`
  (and some `php-fpm` + Apache proxy setups), PHP never sees the incoming `Authorization`
  header unless the web server is configured to forward it. Add to the vhost or `.htaccess`:
  `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` (or the equivalent
  `CGIPassAuth On` directive on Apache 2.4.13+). Without this, every JWT-authenticated
  request will incorrectly 401 in that deployment configuration even with a valid token.
