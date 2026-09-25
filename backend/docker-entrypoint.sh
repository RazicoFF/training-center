#!/bin/sh
set -e

# Railway (and most PaaS hosts) assign a random port via $PORT and route traffic to
# it - Apache's image defaults to a hardcoded port 80, so both the global Listen
# directive and the vhost need to be rewritten to match before Apache starts.
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Idempotent - migrate.php tracks applied migrations in schema_migrations, so this
# is safe to run on every container start/restart, not just the first deploy.
php /var/www/html/database/migrate.php .env

exec "$@"
