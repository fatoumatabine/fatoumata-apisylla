#!/bin/sh

set -e

# Exécuter les migrations de base de données
php artisan migrate --force

# Démarrer Laravel directement (pour Render.com)
# Utiliser la variable d'environnement PORT fournie par Render.com
PORT=${PORT:-8000}
php artisan serve --host=0.0.0.0 --port=$PORT
