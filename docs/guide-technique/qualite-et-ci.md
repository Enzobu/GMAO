# Qualité et CI

La CI construit les images Docker, exécute les tests API avec couverture, lance
SonarQube puis déploie selon la branche ou le tag.

Après un changement backend, la vérification attendue est :

```bash
docker compose --env-file .env.local exec -T apache symfony console cache:clear
docker compose --env-file .env.local exec -T apache vendor/bin/phpunit --coverage-text
```

Après un changement frontend, lancez au minimum :

```bash
cd frontend
npm run build
```
