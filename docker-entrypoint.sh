#!/bin/sh

set -e

# Attendre que la base de données soit prête (utiliser nc si pg_isready n'est pas disponible)
echo "Waiting for database to be ready..."
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"

# Vérifier si les variables sont définies
if [ -z "$DB_HOST" ] || [ -z "$DB_PORT" ]; then
  echo "ERROR: DB_HOST or DB_PORT is not set"
  echo "DB_HOST: '$DB_HOST'"
  echo "DB_PORT: '$DB_PORT'"
  exit 1
fi

timeout=60
counter=0
while ! nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 1
  counter=$((counter + 1))
  if [ $counter -ge $timeout ]; then
    echo "Database connection timeout after $timeout seconds"
    echo "Failed to connect to $DB_HOST:$DB_PORT"
    exit 1
  fi
done

echo "Database is up - executing migrations"
php artisan migrate --force

echo "Running database seeders..."
php artisan db:seed --force

echo "Generating Swagger documentation..."
php artisan l5-swagger:generate

echo "Testing API endpoint..."
curl -s https://fatoumata-apisylla-1.onrender.com/api/v1/comptes | jq .data | head -5

echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Starting Laravel application..."
exec php artisan serve --host=0.0.0.0 --port=8000
