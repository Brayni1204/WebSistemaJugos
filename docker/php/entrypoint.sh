#!/bin/sh
set -e

# Run composer install and Laravel setup commands
composer install --optimize-autoloader --no-dev
php artisan key:generate --ansi
php artisan storage:link

# Set permissions (now that storage and bootstrap/cache exist)
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Execute the main container command (likely php-fpm or tail -f /dev/null)
exec "$@"