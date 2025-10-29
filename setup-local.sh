#!/bin/bash

echo "🚀 Configuration de l'environnement de développement local"
echo "======================================================"

# Vérifier si PostgreSQL est installé
if ! command -v psql &> /dev/null; then
    echo "❌ PostgreSQL n'est pas installé."
    echo "📦 Installation de PostgreSQL..."
    echo "Sur Ubuntu/Debian : sudo apt-get install postgresql postgresql-contrib"
    echo "Sur macOS : brew install postgresql"
    echo "Sur Windows : Téléchargez depuis https://www.postgresql.org/download/windows/"
    exit 1
fi

# Vérifier si le service PostgreSQL est en cours d'exécution
if ! pg_isready -h localhost -p 5432 &> /dev/null; then
    echo "❌ PostgreSQL n'est pas en cours d'exécution."
    echo "🔄 Démarrage de PostgreSQL..."
    echo "Sur Ubuntu/Debian : sudo systemctl start postgresql"
    echo "Sur macOS : brew services start postgresql"
    echo "Sur Windows : Démarrez le service PostgreSQL"
    exit 1
fi

echo "✅ PostgreSQL est installé et en cours d'exécution"

# Créer la base de données si elle n'existe pas
DB_NAME="appcompt"
DB_USER="postgres"

echo "📊 Configuration de la base de données '$DB_NAME'..."

# Créer la base de données (nécessite des droits admin)
sudo -u postgres psql -c "CREATE DATABASE $DB_NAME;" 2>/dev/null || echo "ℹ️  La base de données '$DB_NAME' existe déjà ou vous n'avez pas les droits suffisants"

echo "📋 Copie du fichier .env.example vers .env..."
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✅ Fichier .env créé"
else
    echo "ℹ️  Le fichier .env existe déjà"
fi

echo "🔑 Génération de la clé d'application..."
php artisan key:generate

echo "🗄️  Exécution des migrations..."
php artisan migrate

echo "🌱 Exécution des seeders..."
php artisan db:seed

echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate

echo ""
echo "🎉 Configuration terminée !"
echo "==========================="
echo "Votre application est prête pour le développement local."
echo ""
echo "Commandes utiles :"
echo "- php artisan serve              # Démarrer le serveur"
echo "- php artisan test              # Exécuter les tests"
echo "- php artisan migrate:fresh     # Reset complet de la DB"
echo "- php artisan l5-swagger:generate # Régénérer la doc API"
echo ""
echo "API disponible sur : http://localhost:8000"
echo "Documentation API : http://localhost:8000/api/documentation"
