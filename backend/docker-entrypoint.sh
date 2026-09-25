#!/bin/sh
set -e

# Idempotent - migrate.php tracks applied migrations in schema_migrations, so this
# is safe to run on every container start/restart, not just the first deploy.
php /var/www/html/database/migrate.php .env

exec "$@"
