# eco_ride_back

## mise en place de l' environnement de travail

---

1-**Prérequis :**

- éditeur de code
- server local
- Mysql

2-**Clonage du projet :**

```bash
git clone https://github.com/lili1326/eco_ride_back.git
```

3-**Installation des dépendances :**

```bash
composer install
```

4-**Configuration Bdd :**

```bash
DATABASE_URL="mysql://root:motdepasse@127.0.0.1:3306/nom_base?serverVersion=8&charset=utf8mb4"
```

5-**Création Bdd :**

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

6-**lancer le server :**

```bash
symfony server :start -d
```

7-**Accéder a l’API**
http://127.0.0.1:8000/api/doc

8-**Instalation des outils de développement**

```bash
composer require symfony/orm-pack
composer require --dev symfony/maker-bundle
composer require symfony/security-bundle
composer require symfony/serializer-pack
composer require nelmio/cors-bundle
composer require nelmio/api-doc-bundle
```

9-**Outil supplémentaire :**

- Postman
- Githup
- Symfony CLI

10-**Mode production:**

```bash
 Modifier le fichier .env :
APP_ENV=prod
APP_DEBUG=0

 Générer le cache de prod
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

 Lancer le serveur en mode production
symfony serve --no-tls --env=prod
```

## Données de test (DataFixtures)

Installer

```bash
composer require --dev doctrine/doctrine-fixtures-bundle
composer require --dev fakerphp/faker
```

Des DataFixtures sont disponibles pour remplir automatiquement la base de données MySQL avec des données réalistes de test.

### Ce que les fixtures créent :

- 1 administrateur (`admin@ecoride.com` / `adminpass`)
- 5 utilisateurs (`user1@test.com` → `user5@test.com`, mot de passe `userpass`)
- 5 voitures (1 par utilisateur)
- 10 trajets (2 par utilisateur)
- Préférences associées
- Participations à des trajets
- Avis entre utilisateurs

### Pour charger les données de test ( Cela efface la base !)

```bash
php bin/console doctrine:fixtures:load
```

### Pour ajouter les données sans supprimer les existantes :

```bash
php bin/console doctrine:fixtures:load --append
```

### Gestion des crédits (MongoDB)

Pour que le tableau de bord admin (`/credits-per-day`) affiche des crédits correctement, il faut ajouter des données dans la collection MongoDB `tresorerie`.

Utilisez la commande suivante :

```bash
php bin/console app:populate-mongo-tresorerie
```

Cette commande :

Ajoute +20 crédits pour chaque utilisateur (simulation d'inscription)

Ajoute deux retraits de -2 crédits (simulation de participation à des trajets)
