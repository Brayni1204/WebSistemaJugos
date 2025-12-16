#!/bin/sh
set -e
chown -R www-data:www-data /var/www
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
export COMPOSER_PROCESS_TIMEOUT=600
# php artisan migrate --force
exec "$@"