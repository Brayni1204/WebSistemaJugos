#!/bin/sh

# Overwrite composer.json with the correct version to fix Git sync issues
cat <<'EOF' > composer.json
{
    "$schema": "https://getcomposer.org/schema.json",
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": [
        "laravel",
        "framework"
    ],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "algolia/algoliasearch-client-php": "^4.0",
        "barryvdh/laravel-dompdf": "^3.1",
        "cloudinary-labs/cloudinary-laravel": "^3.0",
        "endroid/qr-code": "^6.0",
        "jeroennoten/laravel-adminlte": "^3.14",
        "laravel/framework": "^11.41",
        "laravel/jetstream": "^5.3",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "laravel/ui": "^4.6",
        "livewire/livewire": "^3.0",
        "lukepolo/laracart": "^2.4",
        "maatwebsite/excel": "^3.1",
        "mercadopago/dx-php": "^3.4",
        "psy/psysh": "*",
        "pusher/pusher-php-server": "^7.2",
        "ratchet/pawl": "^0.4.3",
        "spatie/laravel-permission": "^6.12",
        "stripe/stripe-php": "^16.5"
    },
    "require-dev": {
        "fakerphp/faker": "*",
        "laravel/pail": "^1.1",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "pestphp/pest": "^3.6",
        "pestphp/pest-plugin-laravel": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "dev": "npx concurrently -c \"#93c5fd,#c4b5fd,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"npm run dev\" --names='server,queue,vite'"
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
EOF

# Run composer install and Laravel setup commands
composer install --optimize-autoloader --no-dev

# Generate autoload files again to fix PSR-4 warnings
composer dump-autoload --optimize

# Ensure .env exists
if [ ! -f .env ]; then
    echo "Creating .env file from .env.example"
    cp .env.example .env
fi

php artisan key:generate --force --ansi
php artisan storage:link

# Wait for DB service to be ready
echo "Waiting for Database to be ready..."
php -r "
    \$host = getenv('DB_HOST') ?: '10.60.0.5';
    \$port = getenv('DB_PORT') ?: 3306;
    \$maxTries = 300;
    echo 'Checking connection to ' . \$host . ':' . \$port . '...';
    for (\$i = 0; \$i < \$maxTries; \$i++) {
        \$conn = @fsockopen(\$host, \$port, \$errno, \$errstr, 2);
        if (\$conn) {
            fclose(\$conn);
            echo ' Database is up and reachable!' . PHP_EOL;
            exit(0);
        }
        echo '.';
        sleep(1);
    }
    echo PHP_EOL . 'Could not connect to database host ' . \$host . ':' . \$port . ' after ' . \$maxTries . ' seconds.' . PHP_EOL;
    echo 'Last error: ' . \$errstr . ' (' . \$errno . ')' . PHP_EOL;
    exit(1);
"

if [ $? -ne 0 ]; then
  echo "Database connection failed. Exiting."
  exit 1
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Clear caches
echo "Clearing caches..."
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Set permissions (now that storage and bootstrap/cache exist)
chown -R www-data:www-data /var/www
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

echo "Entrypoint setup complete. Handing over to main command."

# Execute the main container command (likely php-fpm or tail -f /dev/null)
exec "$@"