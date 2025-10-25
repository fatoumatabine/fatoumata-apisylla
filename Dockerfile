# Étape 1 : Build de l’application Laravel
FROM php:8.3-cli AS build

WORKDIR /app

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier tous les fichiers de l’application
COPY . .

# Installer les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Étape 2 : Image finale
FROM php:8.3-fpm

# Installer les extensions nécessaires à Laravel
RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev zip unzip git curl && \
    docker-php-ext-install pdo pdo_pgsql zip && \
    rm -rf /var/lib/apt/lists/*

# Copier les fichiers de l’application depuis l’étape build
COPY --from=build /app /var/www/html

WORKDIR /var/www/html

# Donner les bons droits à storage et bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

# Exposer le port 8000
EXPOSE 8000

# Commande de démarrage
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
