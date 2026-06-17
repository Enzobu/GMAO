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

## Pipeline GitHub Actions

Le workflow principal est `.github/workflows/build.yml`.

Il contient les jobs suivants :

- `docker-build` : construction des images Docker production ;
- `tests` : démarrage de l'environnement, installation API, schéma test,
  PHPUnit avec couverture ;
- `sonarqube` : analyse SonarQube et Quality Gate ;
- `deploy-pre-prod` : déploiement pré-production ;
- `deploy-prod` : déploiement production sur tag.

## SonarQube

La configuration est dans `sonar-project.properties`.

Les sources analysées sont :

- `api/src` ;
- `frontend/src`.

Les tests déclarés sont dans `api/tests`.

La documentation est explicitement exclue des analyses et de la couverture via
`docs/**`.

## Couverture

La CI génère `api/var/coverage/clover.xml`, corrige les chemins pour SonarQube,
puis téléverse le rapport comme artefact.

Après un changement backend, gardez la couverture du code concerné au niveau
attendu et ajoutez les tests nécessaires.

## Qualité frontend

Le frontend doit rester typé et buildable.

Lancez :

```bash
cd frontend
npm run lint
npm run build
```

## Commits

Les commits doivent respecter Conventional Commits.

Exemples :

```text
feat: add maintenance reminder
fix: prevent invalid mileage update
docs: complete technical guide
```

Le hook pre-commit ne doit pas être contourné.
