# Gestion des demandes d'assistance informatique

Application web de tickets d'assistance : PHP 8 + PDO + MariaDB, Bootstrap 5, JavaScript (`fetch`), Docker Compose, GitHub Actions.

## Démarrage avec Docker

Prérequis : Docker et Docker Compose.

```bash
cp .env.example .env        # puis adapter les mots de passe dans .env (jamais committé)
docker compose up --build
```

- Application : <http://localhost:8080> (port modifiable avec `APP_PORT` dans `.env`)
- Au **premier démarrage**, MariaDB exécute automatiquement `db/schema.sql` (création des tables `users`, `tickets`, `reponses`).
- Les données sont dans le volume Docker `db_data` : elles survivent à `docker compose down` puis `up`.
  (`docker compose down -v` supprime aussi le volume, donc les données.)

### Données de démonstration

Dans un second terminal, une fois les conteneurs démarrés :

```bash
docker compose exec app php db/seed.php            # refuse de s'exécuter si la base contient déjà des données
docker compose exec app php db/seed.php --reset    # vide les tables puis recrée les données
```

Le script crée 1 administrateur, 2 utilisateurs, 14 tickets (tous statuts et toutes priorités) et 6 réponses.

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@example.test` | `Admin-Demo-2026!` |
| Utilisateur | `alice@example.test` | `Alice-Demo-2026!` |
| Utilisateur | `bruno@example.test` | `Bruno-Demo-2026!` |

Ce sont des mots de passe de démonstration, sans valeur réelle. Les mots de passe sont stockés hachés (`password_hash`).

## Commandes de vérification

```bash
# Syntaxe PHP (identique à la CI)
find . -name '*.php' -print0 | xargs -0 -n1 php -l

# Fichier Compose avec variables de test (identique à la CI)
docker compose --env-file .env.example config --quiet

# Construction de l'image (identique à la CI)
docker build -t tickets-app:ci .

# Tests de bout en bout (application lancée, base initialisée avec le seed)
tests/smoke.sh http://localhost:8080
```

Le workflow `.github/workflows/ci.yml` exécute les trois premières vérifications à chaque `push`, sans aucun secret.

## Lancement sans Docker (développement)

PHP 8.1+ avec `pdo_mysql` et MariaDB/MySQL :

```bash
mariadb -e "CREATE DATABASE tickets CHARACTER SET utf8mb4"
mariadb tickets < db/schema.sql
export DB_HOST=127.0.0.1 DB_NAME=tickets DB_USER=... DB_PASSWORD=...
php db/seed.php
php -S 127.0.0.1:8080 -t public
```

## Structure du projet

```
public/            seule racine servie par le serveur web
  *.php            pages (traitements + appel des vues)
  api/tickets.php  point d'accès JSON (recherche et filtres)
  assets/css, js   style personnalisé et JavaScript
src/               configuration, accès BDD (PDO), authentification, CSRF, validation, requêtes SQL
  views/           en-tête/pied de page, formulaire, liste, modale (composants réutilisables)
db/                schema.sql (création) et seed.php (données de démonstration)
tests/smoke.sh     tests de bout en bout (curl)
docs/              rapport et captures
```

`src/` et `db/` ne sont pas servis par Apache : seul `public/` est la racine web.

## Choix techniques et bibliothèques

Aucun framework PHP ni bibliothèque PHP complémentaire. Côté navigateur : Bootstrap 5.3 (CSS + JS) chargé depuis le CDN jsDelivr, comme demandé par le sujet. Le JavaScript du projet n'utilise que les API natives (`fetch`, `AbortController`, DOM).

## Sécurité (résumé)

Requêtes préparées PDO partout ; mots de passe avec `password_hash`/`password_verify` ; `session_regenerate_id` après connexion ; cookies de session `HttpOnly` + `SameSite=Lax` ; contrôle de session, de rôle et de propriété des tickets côté serveur (y compris pour l'API JSON) ; jeton CSRF sur toutes les actions qui modifient des données (qui exigent POST, la déconnexion comprise) ; échappement systématique (`htmlspecialchars`) et DOM construit avec `textContent` ; identifiants BDD uniquement dans les variables d'environnement ; erreurs journalisées, jamais affichées.
