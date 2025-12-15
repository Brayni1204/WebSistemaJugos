#!/bin/sh
set -e

# Establece www-data como propietario de todos los archivos en /var/www
# Esto soluciona los problemas de permisos causados por los volúmenes de Docker.
chown -R www-data:www-data /var/www

# Ejecuta las migraciones de la base de datos para asegurar que las tablas existan.
# El --force es necesario para que se ejecute en un entorno no interactivo.
php artisan migrate --force

# Finalmente, ejecuta el comando principal que se le pasa al contenedor
# (en nuestro caso, será "php-fpm").
exec "$@"
