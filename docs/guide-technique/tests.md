# Tests

Les tests API s'exécutent dans le conteneur `apache`.

Commande complète locale :

```bash
docker compose -f docker-compose.yml --env-file .env.local exec apache bin/phpunit
```

Commande ciblée :

```bash
docker compose -f docker-compose.yml --env-file .env.local exec apache bin/phpunit tests/Unit/chemin/Test.php
```

## Préparation du schéma de test

La CI met à jour le schéma de test avant PHPUnit :

```bash
php bin/console d:s:u --force -n --env=test
```

En local, exécutez cette commande dans le conteneur `apache` si les tests
échouent à cause du schéma.

## Couverture

Pour générer la couverture texte localement :

```bash
docker compose --env-file .env.local exec -T apache vendor/bin/phpunit --coverage-text
```

Pour la CI, PHPUnit génère un rapport Clover :

```bash
vendor/bin/phpunit --coverage-clover var/coverage/clover.xml
```

## Frontend

Le frontend n'a pas de suite de tests automatisés dédiée dans le workflow actuel.

Les vérifications attendues sont :

```bash
cd frontend
npm run lint
npm run build
```

## Documentation

La documentation VitePress se vérifie avec :

```bash
cd docs
npm run build
```

## Hooks de commit

Le pre-commit du dépôt lance :

- `cache:clear` Symfony ;
- PHPUnit ;
- lint frontend.

Les conteneurs Docker doivent être démarrés pour que le hook puisse exécuter les
commandes API.
