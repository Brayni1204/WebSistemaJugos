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

# Cambiar propietario del directorio de trabajo antes de cambiar de usuario
USER root
RUN chown -R ${USER}:${USER} /var/www

# Instalar dependencias (como usuario www-data para permisos correctos)
USER ${USER}
RUN COMPOSER_MEMORY_LIMIT=-1 COMPOSER_PROCESS_TIMEOUT=600 composer install --no-scripts --no-autoloader
RUN npm install

# Copiar el resto de la aplicación y establecer permisos
COPY --chown=www-data:www-data . .

# Terminar la instalación de composer, construir assets y optimizar
RUN COMPOSER_MEMORY_LIMIT=-1 COMPOSER_PROCESS_TIMEOUT=600 composer install --optimize-autoloader --no-dev --no-scripts
RUN npm run build

# Cambiar de nuevo a root para cambiar permisos finales
USER root
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]

# Exponer puerto y iniciar FPM
EXPOSE 9000
CMD ["php-fpm"]