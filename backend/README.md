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
   This generates a random password and prints it, along with the admin login phone, once at
   creation time — save it, since it is not stored anywhere and re-running the seeder after the
   admin already exists will not print it again (it short-circuits instead). There is currently
   no password-change UI; an operator who loses the password must reset it directly via SQL
   (using `App\Core\Auth::hashPassword()` to produce a new hash) or by re-running a modified
   copy of the seeder against a cleared row.
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

### Testing status

The full test suite (`vendor/bin/phpunit`) has been run repeatedly against a real PHP 8.3
runtime and a real MariaDB database throughout this project's development, including the
admin panel work — it is not a suite that was only statically reviewed. It currently passes
in full; run it yourself with the command above to confirm on your machine.

## Known gaps

- **No role-based access control beyond admin/teacher login-gating on some pages.**
  `AdminAuthMiddleware::requireAdmin()` now enforces `role = 'admin'` on every admin-only
  page named in the design spec (applications, teachers, tests, questions, certificates, and
  group creation/enrollment) — a `teacher` session is redirected to `/admin/login` on those
  routes, matching the spec. `GroupController`'s read-only pages (list/create-form/show) and
  the shared `/admin` dashboard remain open to both `admin` and `teacher` sessions by design.
  There is no finer-grained permission model beyond this two-role distinction.
- **No question edit/delete UI.** Questions and answers can only be created via
  `POST /admin/tests/{id}/questions`; there is no route or view to edit or delete a question
  or answer once it exists. A mistake must currently be corrected directly in the database.
- **No profession-editing UI.** Professions are seeded via `database/seeders/seed.php` and
  read-only in the admin panel (used to populate dropdowns); there is no admin page to
  create, edit, or delete a profession.
- **No production (Apache/Nginx) rewrite-rule documentation.** Only the dev `router.php`
  script (used with PHP's built-in server, see Setup step 7) is documented here. Serving
  this two-front-controller setup (`public/index.php` for the API, `public/admin.php` for
  the admin panel) behind Apache or Nginx in production requires rewrite rules routing
  `/admin` and `/admin/*` to `public/admin.php` and everything else to `public/index.php`,
  which are not yet written or documented.
- **Apache + CGI/FastCGI may strip the `Authorization` header.** Under `mod_cgi`/`mod_fcgid`
  (and some `php-fpm` + Apache proxy setups), PHP never sees the incoming `Authorization`
  header unless the web server is configured to forward it. Add to the vhost or `.htaccess`:
  `SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1` (or the equivalent
  `CGIPassAuth On` directive on Apache 2.4.13+). Without this, every JWT-authenticated
  request will incorrectly 401 in that deployment configuration even with a valid token.
