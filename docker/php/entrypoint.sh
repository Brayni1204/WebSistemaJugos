#!/bin/sh
set -e

# Solo ajustamos permisos en carpetas criticas para escritura
chown -R www-data:www-data /var/www

# Crear directorios si no existen
mkdir -p /var/www/storage
mkdir -p /var/www/bootstrap/cache
mkdir -p /var/www/bootstrap/cache/storage

# Dar permisos de escritura a todos para depuración extrema de permisos
# ¡ADVERTENCIA: NO USAR EN PRODUCCIÓN! Solo para depuración.
chmod -R 777 /var/www/storage /var/www/bootstrap/cache

# Aumenta el tiempo de espera de Composer para procesos (ej. extracción de archivos)
export COMPOSER_PROCESS_TIMEOUT=600

# Ejecuta las migraciones de la base de datos para asegurar que las tablas existan.
# El --force es necesario para que se ejecute en un entorno no interactivo.
php artisan migrate --force

# Finalmente, ejecuta el comando principal que se le pasa al contenedor
# (en nuestro caso, será "php-fpm").
exec "$@"
