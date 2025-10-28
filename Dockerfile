# Étape 1: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP sans scripts post-install
RUN composer install --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 2: Image finale pour l'application
FROM php:8.3-fpm-alpine

# Installer les extensions PHP nécessaires, netcat
RUN apk add --no-cache postgresql-dev postgresql-client netcat-openbsd jq \
&& docker-php-ext-install pdo pdo_pgsql \
&& echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor


# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs/supervisor \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache \
    && ls -la /var/www/html/vendor # Vérifier les permissions après la copie

# Les commandes de génération de clé et de cache seront gérées par Render ou au démarrage de l'application.

# Copier le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Nginx not needed for Render.com deployment

# Passer à l'utilisateur non-root
USER laravel

# Render.com gère automatiquement l'exposition des ports

# Utiliser le script d'entrée
ENTRYPOINT ["docker-entrypoint.sh"]
