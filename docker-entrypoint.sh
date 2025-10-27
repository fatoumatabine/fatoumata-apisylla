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
ls -l storage/api-docs/

echo "Testing API endpoint..."
curl -s https://fatoumata-apisylla-1.onrender.com/api/v1/comptes | jq .data | head -5

echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "Starting Laravel application services via start.sh..."
# Ajouter un délai pour s'assurer que tout est prêt avant de démarrer supervisord
sleep 5

# Exécuter start.sh qui démarre supervisord
exec /bin/sh start.sh

# Afficher les logs de supervisord pour le débogage (si le conteneur reste en vie)
echo "Supervisor logs for php-fpm:"
cat /var/log/supervisor/php-fpm.log || echo "php-fpm log not found"
echo "Supervisor logs for nginx:"
cat /var/log/supervisor/nginx.log || echo "nginx log not found"
