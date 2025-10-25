#!/bin/sh

set -e

# Attendre que la base de données soit prête (utiliser nc si pg_isready n'est pas disponible)
echo "Waiting for database to be ready..."
while ! nc -z $DB_HOST $DB_PORT; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

echo "Starting Laravel application..."
exec php artisan serve --host=0.0.0.0 --port=8000
