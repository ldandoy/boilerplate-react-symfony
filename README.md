<div align="center">

# Boilerplate SaaS Symfony / React

Base de départ pour une application SaaS : API Symfony + API Platform, front React + Vite, authentification JWT complète, le tout sous Docker.

[![CI](https://github.com/ldandoy/boilerplate-react-symfony/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/ldandoy/boilerplate-react-symfony/actions/workflows/ci.yml)

![PHP](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fldandoy%2Fboilerplate-react-symfony%2Fmain%2Fbackend%2Fcomposer.json&query=%24.require.php&label=PHP&logo=php&logoColor=white&color=777BB4)
![Symfony](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fldandoy%2Fboilerplate-react-symfony%2Fmain%2Fbackend%2Fcomposer.json&query=%24.require%5B%27symfony%2Fframework-bundle%27%5D&label=Symfony&logo=symfony&logoColor=white&color=000000)
![API Platform](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fldandoy%2Fboilerplate-react-symfony%2Fmain%2Fbackend%2Fcomposer.json&query=%24.require%5B%27api-platform%2Fsymfony%27%5D&label=API%20Platform&color=38A9B4)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)

![Node](https://img.shields.io/badge/Node-24-5FA04E?logo=nodedotjs&logoColor=white)
![pnpm](https://img.shields.io/badge/dynamic/json?url=https%3A%2F%2Fraw.githubusercontent.com%2Fldandoy%2Fboilerplate-react-symfony%2Fmain%2Ffrontend%2Fpackage.json&query=%24.packageManager&label=pnpm&logo=pnpm&logoColor=white&color=F69220)
![React](https://img.shields.io/github/package-json/dependency-version/ldandoy/boilerplate-react-symfony/react?filename=frontend%2Fpackage.json&label=React&logo=react&logoColor=black&color=61DAFB)
![Vite](https://img.shields.io/github/package-json/dependency-version/ldandoy/boilerplate-react-symfony/dev/vite?filename=frontend%2Fpackage.json&label=Vite&logo=vite&logoColor=white&color=646CFF)
![TypeScript](https://img.shields.io/github/package-json/dependency-version/ldandoy/boilerplate-react-symfony/dev/typescript?filename=frontend%2Fpackage.json&label=TypeScript&logo=typescript&logoColor=white&color=3178C6)
![Tailwind CSS](https://img.shields.io/github/package-json/dependency-version/ldandoy/boilerplate-react-symfony/tailwindcss?filename=frontend%2Fpackage.json&label=Tailwind&logo=tailwindcss&logoColor=white&color=06B6D4)

</div>

---

## Fonctionnalités

- **Authentification JWT** : connexion, refresh token, déconnexion (invalidation du refresh token)
- **Inscription** avec **vérification de l'email**
- **Mot de passe oublié** et réinitialisation par email
- **Profil utilisateur** (`/api/me`)
- **Back-office** EasyAdmin sur `/admin`, réservé à `ROLE_ADMIN`
- **Documentation OpenAPI / Swagger** générée par API Platform
- **Front React** : routing, formulaires validés avec Zod, thème clair/sombre, notifications toast
- **Emails en local** interceptés par Mailpit
- **CI GitHub Actions** : tests, lint, audit de sécurité et build à chaque push

## Stack

| Couche | Technologies |
| --- | --- |
| Backend | PHP 8.4, Symfony 8.1, API Platform 4, Doctrine ORM 3, LexikJWT, FrankenPHP |
| Frontend | React 19, TypeScript, Vite, Tailwind CSS 4, React Router, React Hook Form + Zod, Axios |
| Base de données | MySQL 8.0 |
| Tests | PHPUnit 12 (unitaires + fonctionnels), Vitest + Testing Library |
| Outillage | Docker Compose, pnpm, ESLint, Prettier, Xdebug |

## Démarrage rapide

```bash
git clone git@github.com:ldandoy/boilerplate-react-symfony.git
cd boilerplate-react-symfony
cp frontend/.env.example frontend/.env   # puis ajuster VITE_API_URL si besoin
docker compose up -d --build
```

Générer les clés JWT et créer le schéma de la base :

```bash
docker exec -it boilterplate_back sh
php bin/console lexik:jwt:generate-keypair --skip-if-exists
php bin/console doctrine:migrations:migrate -n
```

### Services

| Service | URL |
| --- | --- |
| Front React | http://localhost:5173 |
| API + Swagger | http://localhost:8001/api |
| Back-office | http://localhost:8001/admin |
| phpMyAdmin | http://localhost:8080 |
| Mailpit | http://localhost:8025 |

## Créer des utilisateurs

À lancer dans le conteneur `back` :

```bash
# Utilisateur simple
php bin/console app:create-user user@test.com testtest

# Administrateur avec email vérifié
php bin/console app:create-user admin@test.com testtest --admin --verified
```

## Tests

### Backend (PHPUnit)

À lancer dans le conteneur `back` (`docker exec -it boilterplate_back sh`).

Première fois uniquement, pour générer les clés JWT et la base de test :

```bash
php bin/console lexik:jwt:generate-keypair --env=test --skip-if-exists
php bin/console doctrine:database:create --env=test --if-not-exists
```

Lancer les tests :

```bash
bin/phpunit                        # tout
bin/phpunit --testsuite unit       # tests unitaires
bin/phpunit --testsuite functional # tests HTTP de chaque route de l'API (base app_test)
```

### Frontend (Vitest)

```bash
docker exec -it boilterplate_front sh
pnpm test            # une passe
pnpm test:watch      # mode watch
pnpm test:coverage   # avec couverture (rapport dans frontend/coverage)
pnpm lint
```

## Intégration continue

Le workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml) tourne sur chaque push vers `main` ou `develop` et sur chaque pull request vers `main` :

| Job | Étapes |
| --- | --- |
| **Tests PHPUnit** | `composer install`, `composer audit`, clés JWT + base de test, `bin/phpunit` |
| **Tests Vitest** | `pnpm install`, `pnpm audit`, `pnpm lint`, `pnpm test:coverage`, `pnpm build` |

Le badge **CI** en haut de cette page reflète l'état du dernier run sur `main`.

## Structure

```
.
├── backend/            # API Symfony
│   ├── src/
│   │   ├── ApiResource/    # Ressources API Platform (register, me, reset-password…)
│   │   ├── State/          # Providers / processors API Platform
│   │   ├── Entity/         # Entités Doctrine
│   │   └── Controller/     # Back-office EasyAdmin
│   └── tests/              # Unit + Functional
├── frontend/           # App React
│   └── src/
│       ├── pages/          # Login, Register, Me, ForgotPassword…
│       ├── context/        # Auth, thème, toasts
│       ├── schemas/        # Validation Zod
│       └── lib/            # Client Axios, gestion JWT
├── config/             # Caddyfile, php.ini
└── docker-compose.yml
```
