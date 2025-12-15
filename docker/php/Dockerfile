# Versión de PHP
FROM php:8.2-fpm

# Argumentos para el usuario y UID
ARG USER=www-data
ARG UID=1000

# Instalar dependencias del sistema y Node.js
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libicu-dev \
    nodejs \
    npm

# Limpiar cache de apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql zip exif pcntl intl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Sincronizar el UID y GID del usuario www-data con los argumentos
RUN groupmod -o -g ${UID} ${USER} && \
    usermod -o -u ${UID} -g ${USER} ${USER}
RUN mkdir -p /home/${USER}/.composer && \
    chown -R ${USER}:${USER} /home/${USER}

# Establecer el directorio de trabajo
WORKDIR /var/www

# Copiar archivos de dependencias y establecer permisos
COPY --chown=www-data:www-data composer.json composer.lock ./
COPY --chown=www-data:www-data package.json package-lock.json ./

# Instalar dependencias (como usuario www-data para permisos correctos)
USER ${USER}
RUN composer install --no-scripts --no-autoloader
RUN npm install

# Copiar el resto de la aplicación y establecer permisos
COPY --chown=www-data:www-data . .

# Terminar la instalación de composer, construir assets y optimizar
RUN composer install --optimize-autoloader --no-dev
RUN npm run build
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Cambiar de nuevo a root para cambiar permisos finales
USER root
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Exponer puerto y iniciar FPM
EXPOSE 9000
CMD ["php-fpm"]