# Training Center Backend

PHP 8.3 + MySQL backend serving three clients from one codebase: the JSON API (Android app),
the server-rendered admin panel, and the public website + student portal.

## Local setup

1. `composer install`
2. Copy `.env.example` to `.env` and `.env.testing`, fill in DB credentials.
3. Generate a real `JWT_SECRET` for each env file: `php -r "echo bin2hex(random_bytes(32));"`.
4. Create databases: `training_center` and `training_center_test` (utf8mb4).
5. Run migrations: `php database/migrate.php .env` and `php database/migrate.php .env.testing`
6. Seed professions: `php database/seeders/seed.php .env` and `.env.testing`
7. Create the first admin account: `php database/seeders/seed_admin.php .env` (prints a
   generated login/password once — save it).
8. Serve locally: `php -S localhost:8080 -t public router.php`

`router.php` dispatches `/admin/*` to the admin panel, a fixed list of public-site paths to
the site controller, and everything else to the JSON API. It is a dev-server-only helper;
`Dockerfile` + `docker/nginx.conf` implement the same routing for production.

## Tests

`vendor/bin/phpunit`

## Production deployment

See `DEPLOYMENT.md` for the full Docker + Railway setup (environment variables, MySQL,
persistent storage for uploads and certificates).
