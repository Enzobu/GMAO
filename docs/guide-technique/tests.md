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
