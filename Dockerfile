FROM php:8.2-fpm

# Configuración de zona horaria (opcional, ajusta a tu zona horaria)
ENV TZ=America/Bogota

# Establecer el usuario www-data desde el principio
USER www-data

# Actualizar repositorios y asegurar certificados
USER root
RUN apt-get update && apt-get install -y apt-transport-https ca-certificates lsb-release

# Instalar dependencias del sistema y extensiones de PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    libzip-dev \
    libxml2-dev \
    curl \
    libonig-dev \
    libpng-dev \
    default-mysql-client \
    && docker-php-ext-configure pdo_pgsql \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mysqli \
    zip \
    xml \
    mbstring \
    gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar el archivo de dependencias y ejecutar Composer
COPY --chown=www-data:www-data composer.json ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Copiar el código de la aplicación con los permisos correctos directamente
COPY --chown=www-data:www-data . .

# Crear y ajustar permisos solo para directorios específicos que necesitan escritura
RUN mkdir -p /var/www/html/uploads /var/www/html/cache /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/cache /var/www/html/logs

# Cambiar al usuario www-data para la ejecución
USER www-data

CMD ["php-fpm"]
