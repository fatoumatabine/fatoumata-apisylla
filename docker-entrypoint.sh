#!/bin/sh

set -e

# Attendre que la base de données soit prête
echo "Waiting for database to be ready..."
while ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USERNAME; do
  echo "Database is unavailable - sleeping"
  sleep 1
done

echo "Database is up - executing migrations"
php artisan migrate --force

echo "Starting Laravel application..."
exec php artisan serve --host=0.0.0.0 --port=8000
