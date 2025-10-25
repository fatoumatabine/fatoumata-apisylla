#!/bin/sh

set -e

# Exécuter les migrations de base de données
php artisan migrate --force

# Démarrer le serveur Nginx/PHP-FPM
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
