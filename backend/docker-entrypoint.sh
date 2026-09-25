#!/bin/sh
set -e

# Idempotent - migrate.php tracks applied migrations in schema_migrations, so this
# is safe to run on every container start/restart, not just the first deploy.
php /var/www/html/database/migrate.php .env

# php-fpm daemonizes itself (-D) and returns immediately; nginx (passed in as "$@")
# then runs in the foreground as this container's main process.
php-fpm -D

exec "$@"
