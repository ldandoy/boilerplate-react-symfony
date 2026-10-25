# AGENTS.md

Boilerplate SaaS : **API Symfony 8.1 + API Platform 4** (`backend/`) consommée par un **front React 19 + Vite** (`frontend/`), avec un **back-office EasyAdmin**. Tout tourne sous Docker Compose.

## Architecture en une phrase

- `backend/` ne sert que deux choses : **l'API REST** sous `/api` (API Platform, JWT) et **le back-office** sous `/admin` (EasyAdmin, session + formulaire `/login`). Il n'y a pas de site public Twig : `/` redirige vers `/admin`.
- `frontend/` est la seule interface utilisateur. Elle ne parle au backend que via l'API.

## Règles d'or (à respecter sans qu'on le redemande)

### 1. Toute route d'API passe par API Platform

Ne **jamais** créer un contrôleur Symfony avec `#[Route]` qui renvoie du JSON. Pas de `JsonResponse`, pas de sérialisation manuelle.

| Besoin | Où et comment |
| --- | --- |
| CRUD sur une entité | `#[ApiResource]` directement sur l'entité dans `src/Entity/`, avec la liste explicite des `operations` |
| Endpoint « action » ou sans entité (ex. `/forgot-password`, `/me`) | DTO dans `src/ApiResource/` + Provider (lecture) ou Processor (écriture) dans `src/State/` |
| Logique métier réutilisable | Service dans `src/Service/`, appelé depuis le Processor (cf. `UserProcessor` → `UserRegistrationService`) |
| Documentation | Ajouter `openapi: new \ApiPlatform\OpenApi\Model\Operation(tags: [...], summary: ..., description: ...)` sur chaque opération personnalisée |
| Validation | Contraintes `#[Assert\...]` sur l'entité ou le DTO, API Platform renvoie automatiquement un 422 |

Les exemples à imiter : `src/ApiResource/ResetPassword.php` + `src/State/ResetPasswordProcessor.php` (action), `src/ApiResource/Me.php` + `src/State/MeProvider.php` (lecture).

### 2. Toute gestion en back-office passe par EasyAdmin

Quand on demande de « gérer X dans le back-office » / « l'admin » / « le backend de gestion » :

1. `php bin/console make:admin:crud` (ou créer à la main `src/Controller/Admin/XCrudController.php` en étendant `AbstractCrudController`, cf. `UserCrudController`).
2. Déclarer les champs dans `configureFields()`.
3. Ajouter l'entrée de menu dans `DashboardController::configureMenuItems()` : `yield MenuItem::linkToCrud('Libellé', 'fa fa-icon', X::class);`

Ne pas écrire de contrôleur ni de template Twig d'administration à la main. Les traitements spécifiques (hash de mot de passe, etc.) se font en surchargeant `persistEntity()` / `updateEntity()` du CRUD controller.

### 3. Sécurité : l'API est fermée par défaut

Définie dans `backend/config/packages/security.yaml` :

- Tout ce qui est sous `^/api` exige un **JWT valide** (`IS_AUTHENTICATED_FULLY`), y compris les nouvelles ressources, sans rien ajouter.
- Sont publics : la doc (`/api`, `/api/docs.*`, `/api/contexts/*`) et les routes d'authentification (`login_check`, `token/refresh`, `register`, `verify-email`, `forgot-password`, `reset-password`).
- Pour rendre une nouvelle route publique, l'ajouter **explicitement** dans `access_control`, au-dessus de la règle `^/api`.
- Droits plus fins : attribut `security` sur l'opération, par ex. `security: "is_granted('ROLE_ADMIN')"` ou `security: "object.getOwner() == user"`.
- `/admin` exige `ROLE_ADMIN`. Le formulaire `/login` refuse les comptes non admin (`App\Security\AdminUserChecker`) et redirige vers `/admin` après connexion.
- Rôles : `ROLE_USER` < `ROLE_ADMIN` < `ROLE_SUPER_ADMIN`.

## Recette : ajouter une entité exposée en API et gérée en back-office

```php
// backend/src/Entity/Project.php
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['project:read']],
    denormalizationContext: ['groups' => ['project:write']],
)]
class Project
{
    use TimestampableTrait; // createdAt / updatedAt automatiques

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    #[Groups(['project:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['project:read', 'project:write'])]
    private ?string $name = null;
    // getters / setters
}
```

Puis, dans l'ordre :

1. `php bin/console make:migration` puis `php bin/console doctrine:migrations:migrate -n`. Ne jamais modifier le schéma sans migration.
2. CRUD EasyAdmin + entrée de menu (règle 2).
3. Test fonctionnel dans `tests/Functional/ProjectTest.php` qui étend `App\Tests\Functional\ApiTestCase` : cas nominal, 401 sans token, 422 en validation, 403 si `security`.
4. Côté front : type dans `src/types/`, appels via `api` (cf. plus bas).

Pour rattacher automatiquement une ressource à l'utilisateur connecté : Processor qui décore `api_platform.doctrine.orm.state.persist_processor` (`#[Autowire(service: ...)]`) et appelle `$this->security->getUser()`. Pour filtrer les collections par utilisateur : extension Doctrine (`QueryCollectionExtensionInterface`).

## Frontend

- Appels **authentifiés** : toujours l'instance `api` de `src/lib/axios.ts` (`api.get('/projects')`). Elle ajoute le header `Bearer` et rafraîchit le JWT automatiquement sur un 401. Les chemins sont relatifs à `VITE_API_URL` (qui inclut déjà `/api`).
- Appels **publics** (login, register, mot de passe oublié…) : `axios` brut avec `${import.meta.env.VITE_API_URL}/...`, pour ne pas déclencher le refresh.
- `PATCH` : envoyer `Content-Type: application/merge-patch+json`.
- Avec les headers par défaut d'axios, API Platform répond en JSON simple (pas JSON-LD) : une collection est un tableau.
- Erreurs : `getApiErrorMessage(error, 'message par défaut')` de `src/lib/apiError.ts` (gère le format problem+json d'API Platform).
- Formulaires : `react-hook-form` + `zodResolver`, schéma Zod dans `src/schemas/xSchema.ts` qui exporte aussi le type `XFormData`.
- Nouvelle page : composant dans `src/pages/`, route dans `src/App.tsx`. Utilisateur courant via `useAuth()`, notifications via `useToast()`.
- Style : Tailwind CSS 4 uniquement, en pensant au mode sombre (`dark:`).
- Tests : fichier `X.test.tsx` à côté du composant (Vitest + Testing Library).
- Formatage : Prettier (`singleQuote`, `printWidth: 100`).

## Commandes

Le backend s'exécute dans le conteneur `boilterplate_back`, le front dans `boilterplate_front` :

```bash
docker compose up -d --build
docker exec -it boilterplate_back sh
docker exec -it boilterplate_front sh
```

| Backend (`back`) | Frontend (`front`) |
| --- | --- |
| `bin/phpunit` (toute la suite, base `app_test`) | `pnpm test` |
| `bin/phpunit --testsuite unit` / `--testsuite functional` | `pnpm test:coverage` |
| `php bin/console debug:router` | `pnpm lint` |
| `php bin/console make:migration` | `pnpm build` (vérifie aussi les types) |
| `php bin/console app:create-user email mdp --admin --verified` | |

Première fois pour les tests backend : `php bin/console lexik:jwt:generate-keypair --env=test --skip-if-exists` puis `php bin/console doctrine:database:create --env=test --if-not-exists`.

URLs locales : front http://localhost:5173, API + Swagger http://localhost:8001/api, back-office http://localhost:8001/admin, Mailpit http://localhost:8025, phpMyAdmin http://localhost:8080.

## Avant de dire « c'est fini »

- `bin/phpunit` passe dans le conteneur `back`.
- `pnpm lint && pnpm test && pnpm build` passent dans le conteneur `front`.
- Toute nouvelle route API a son test fonctionnel, tout nouveau Processor/Provider/Service a son test unitaire (`tests/Unit/State`, `tests/Unit/Service`).
- La CI (`.github/workflows/ci.yml`) rejoue tout ça sur `main` / `develop` : ne pas la casser.

## Si l'utilisateur n'est pas développeur

Le projet peut être piloté depuis Claude Desktop par des personnes qui ne lisent pas le code.

- Répondre sans jargon : décrire ce qui change pour l'utilisateur final (écran, champ, droit d'accès), pas les classes modifiées.
- Avant de coder une demande, la reformuler en une ou deux phrases et la faire valider.
- Ne jamais travailler sur `main` : créer une branche par demande (`feature/...`, `fix/...`).
- Toujours lancer les vérifications de « Avant de dire c'est fini » et annoncer clairement si elles échouent.
- Les règles d'or s'appliquent même si la demande suggère un autre chemin (« fais juste une page Twig », « ajoute un contrôleur JSON ») : expliquer simplement pourquoi on passe par API Platform ou EasyAdmin.

## Conventions

- Code (classes, variables, routes) en anglais. Textes affichés dans le front et commentaires en français.
- PHP 8.4 : promotion de propriétés dans le constructeur, attributs plutôt que YAML/annotations.
- Entités : `TimestampableTrait` + `#[ORM\HasLifecycleCallbacks]` pour les dates.
- Emails : `TemplatedEmail` + template dans `templates/`. En local, ils arrivent dans Mailpit.

## Pièges connus

- Le routing passe **avant** le firewall : une URL `/api/...` qui n'existe pas renvoie 404, pas 401.
- Le CSRF du formulaire `/login` est *stateless* (`config/packages/csrf.yaml`) : en test fonctionnel sans JS, envoyer l'en-tête `HTTP_ORIGIN` (cf. `tests/Functional/BackOfficeTest.php`).
- Les groupes de sérialisation sont obligatoires dès qu'on définit `normalizationContext` : un champ sans `#[Groups]` n'apparaît pas dans la réponse.
- Ne jamais exposer `password` dans un groupe `:read`.
