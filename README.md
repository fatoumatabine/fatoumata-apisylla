# 🏦 API Comptes Bancaires

Une API REST complète pour la gestion de comptes bancaires avec Laravel 11, PostgreSQL et architecture SOLID.

## 🚀 Fonctionnalités

### ✅ Comptes Bancaires
- Création de comptes (Épargne/Chèque)
- Gestion des clients
- Blocage/Déblocage automatique des comptes épargne
- Archivage automatique des comptes expirés
- Calcul automatique du solde (dépôts - retraits)

### ✅ Transactions
- Dépôts et retraits avec validation métier
- Historique des transactions
- Archivage automatique vers base externe (Neon PostgreSQL)
- Notifications SMS automatiques

### ✅ Architecture Propre
- **SOLID Principles** respectés
- **Observer Pattern** pour les règles métier
- **Service Layer** pour la logique métier
- **Interfaces** pour éviter le couplage fort
- **Tests automatisés** complets

## 📋 Prérequis

- PHP 8.3+
- Composer
- PostgreSQL 13+
- Node.js & NPM (pour les assets)

## 🛠️ Installation

### 1. Cloner le projet
```bash
git clone <repository-url>
cd appcompt
```

### 2. Installation des dépendances
```bash
composer install
npm install && npm run build
```

### 3. Configuration de l'environnement

#### Option A : Configuration PostgreSQL locale (recommandé)
```bash
# Exécuter le script d'installation automatique
./setup-local.sh
```

#### Option B : Configuration manuelle
```bash
# Copier le fichier d'exemple
cp .env.example .env

# Modifier .env selon votre configuration PostgreSQL
# Puis exécuter :
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan l5-swagger:generate
```

### 4. Démarrer l'application
```bash
php artisan serve
```

L'API sera disponible sur : `http://localhost:8000`

## 📚 Documentation API

La documentation complète est générée automatiquement avec Swagger :

- **Interface Swagger** : `http://localhost:8000/api/documentation`
- **JSON OpenAPI** : `http://localhost:8000/api/docs`

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 🚀 Déploiement

### Render.com (recommandé)

1. **Build & Push** votre image Docker :
```bash
docker build -t votre-app .
docker tag votre-app votre-registry/votre-app:latest
docker push votre-registry/votre-app:latest
```

2. **Configuration Render.com** :
   - Service : Web Service
   - Environment : Docker
   - Database : PostgreSQL (inclus)

3. **Variables d'environnement** (dans render.yaml) :
   ```yaml
   envVars:
     - key: APP_KEY
       value: "base64:votre-cle-generée"
     - key: APP_ENV
       value: production
     - key: DB_CONNECTION
       value: pgsql
     # ... autres variables depuis la DB Render
   ```

## 🔧 Architecture

### 📁 Structure des dossiers
```
app/
├── Contracts/          # Interfaces (SmsServiceInterface, TransactionArchiveInterface)
├── Events/            # Événements (TransactionCreated, ClientCreated)
├── Jobs/              # Tâches en file (ArchiveDailyTransactions)
├── Listeners/         # Écouteurs d'événements (SendTransactionNotification)
├── Models/            # Modèles Eloquent (Compte, Transaction, Client)
├── Observers/         # Observers (TransactionObserver)
├── Services/          # Services métier (SmsService, TransactionService)
├── Http/Controllers/  # Contrôleurs API
└── Console/Commands/  # Commandes Artisan

tests/                 # Tests automatisés
├── Feature/          # Tests d'intégration API
└── Unit/             # Tests unitaires
```

### 🎯 Principes SOLID Implémentés

1. **Single Responsibility** : Chaque classe a une responsabilité unique
2. **Open/Closed** : Interfaces permettent l'extension sans modification
3. **Liskov Substitution** : Implémentations interchangeables
4. **Interface Segregation** : Interfaces spécifiques et ciblées
5. **Dependency Inversion** : Injection via interfaces

### 🔄 Flux de données

```
Transaction créée → Observer → Validation → Événement → Listener → SMS
                                ↓
                            Job d'archivage → Base externe
```

## 📊 Endpoints API

### Comptes
- `GET /api/v1/comptes` - Lister tous les comptes
- `POST /api/v1/comptes` - Créer un compte
- `GET /api/v1/comptes/{id}` - Détails d'un compte
- `PATCH /api/v1/comptes/{id}/block` - Bloquer un compte
- `PATCH /api/v1/comptes/{id}/unblock` - Débloquer un compte
- `DELETE /api/v1/comptes/{id}` - Supprimer un compte

### Transactions
- `GET /api/v1/comptes/{id}/transactions` - Historique des transactions
- `POST /api/v1/comptes/{id}/transactions` - Nouvelle transaction
- `GET /api/v1/comptes/{id}/transactions/{transactionId}` - Détails transaction

## 🔐 Sécurité

- **Validation stricte** des données d'entrée
- **Vérifications métier** avant chaque transaction
- **Archivage sécurisé** des données sensibles
- **Logs détaillés** pour l'audit

## 📈 Performance

- **Pagination automatique** sur toutes les listes
- **Index de base de données** optimisés
- **Cache intelligent** des requêtes fréquentes
- **Archivage automatique** pour réduire la charge

## 🧪 Tests Automatisés

```bash
# Tests de fonctionnalités
php artisan test tests/Feature/

# Tests unitaires
php artisan test tests/Unit/

# Tests avec rapport HTML
php artisan test --coverage-html=reports/coverage
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Auteurs

- **Fatoumata Sylla** - *Développement initial*

## 🙏 Remerciements

- Laravel Framework
- PostgreSQL
- Swagger/OpenAPI
- Render.com
- Toute la communauté PHP/Laravel
