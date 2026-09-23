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
7. Serve locally: `php -S localhost:8080 -t public router.php` (the router script dispatches
   `/admin` and `/admin/*` to the admin panel and everything else to the JSON API; PHP's
   built-in server has no path-based routing of its own, so without it every request falls
   back to `public/index.php`)

## Tests

`vendor/bin/phpunit`

## API

See `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the full endpoint list and DB schema.

## Admin panel

Server-rendered at `/admin` (session-authenticated). Setup:

1. After running migrations and seeders (see Setup above), also run: `php database/seeders/seed_admin.php .env`
   This prints the admin login phone and a one-time password — save it, it is not shown again.
2. Serve alongside the API: `php -S localhost:8080 -t public router.php` (both `public/index.php` and `public/admin.php` are served from the same document root; the router script at `backend/router.php` dispatches `/` and `/admin/*` requests to `public/admin.php` and everything else to `public/index.php`).
3. Open `http://localhost:8080/admin/login`.

### Manual smoke test (after `composer install` + migrate + seed + seed_admin)

1. Log in at `/admin/login` with the seeded admin credentials.
2. `/admin/applications` — submit a `POST /api/v1/applications` via curl first (see API section), then approve it here with a password you choose; confirm the student can then log in via `POST /api/v1/auth/login` with that password.
3. `/admin/teachers/create` — add a teacher.
4. `/admin/groups/create` — create a group for the approved student's profession, pick the new teacher, set a 2-week date range and two weekdays; confirm `/admin/groups/{id}` shows the expected number of schedule rows (visible indirectly via `GET /api/v1/me/schedule` once the student is enrolled).
5. On the group's show page, enroll the approved student.
6. `/admin/tests/create` — create a test for the same profession, then add at least one question with 2+ answers and mark one correct.
7. As the student (via the API, `POST /api/v1/me/tests/{id}/submit`), submit the correct answer and confirm a 100% pass.
8. `/admin/certificates` — confirm the certificate now appears (auto-issued by Task 11's change) and the download link serves a real PDF.

### Known gaps carried over from sub-project 1

See the "Known gaps" section already in this README (no PHP available in the development environment, `Authorization` header stripping under some server configs, etc.) — those apply equally to the admin panel's own testing status: every test in this sub-project was also written and statically reviewed without ever being executed, until the human user's first real `composer install` + `vendor/bin/phpunit` run.

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
