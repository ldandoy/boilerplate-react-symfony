# Projet boilerplate SAAS Symfony / React

## Création d'utilisateur

### Créer un utilisateur simple
php bin/console app:create-user user@test.com testtest

### Créer un administrateur vérifié
php bin/console app:create-user admin@test.com testtest --admin --verifie
## Tests (backend)

À lancer dans le conteneur `back` (`docker exec -it boilterplate_back sh`).

### Première fois : clés JWT et base de test
php bin/console lexik:jwt:generate-keypair --env=test --skip-if-exists
php bin/console doctrine:database:create --env=test --if-not-exists

### Lancer les tests
bin/phpunit                        # tout
bin/phpunit --testsuite unit       # tests unitaires
bin/phpunit --testsuite functional # tests HTTP de chaque route de l'API (base app_test)
