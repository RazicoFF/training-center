# Training Center Backend

PHP + MySQL REST API for the training center Android app.

## Setup

1. `composer install`
2. Copy `.env.example` to `.env` and `.env.testing`, fill in DB credentials.
3. Create databases: `training_center` and `training_center_test` (utf8mb4).
4. Run migrations: `php database/migrate.php .env` and `php database/migrate.php .env.testing`
5. Seed professions: `php database/seeders/seed.php .env` and `php database/seeders/seed.php .env.testing`
6. Serve locally: `php -S localhost:8080 -t public`

## Tests

`vendor/bin/phpunit`

## API

See `docs/superpowers/specs/2026-09-22-backend-db-design.md` for the full endpoint list and DB schema.
