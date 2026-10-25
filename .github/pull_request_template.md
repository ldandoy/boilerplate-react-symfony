## Ce que fait cette PR

<!-- En quelques phrases, du point de vue de l'utilisateur final. -->

## Checklist (cf. AGENTS.md)

- [ ] Les routes d'API passent par API Platform (pas de contrôleur JSON)
- [ ] La gestion en back-office passe par un CrudController EasyAdmin + entrée de menu
- [ ] Toute modification d'entité a sa migration (`make:migration`)
- [ ] Nouvelle route API → test fonctionnel ; nouveau Processor / Provider / Service → test unitaire
- [ ] `bin/phpunit` passe dans le conteneur `back`
- [ ] `pnpm lint && pnpm test && pnpm build` passent dans le conteneur `front`
