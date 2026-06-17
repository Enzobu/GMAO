# Déploiement

Le workflow CI contient les étapes de déploiement pré-production et production.

La pré-production est déclenchée depuis la branche dédiée. La production est
déclenchée par tag selon la configuration GitHub Actions.

Avant un déploiement, vérifiez les migrations, les variables d'environnement et
les dépendances aux documents uploadés.

## Images production

Le fichier `docker-compose.prod.yml` construit :

- l'API avec `api/Dockerfile.prod` ;
- le frontend avec `frontend/Dockerfile.prod` ;
- la documentation avec `docs/Dockerfile.prod`.

Le frontend reçoit les arguments de build :

- `VITE_API_URL` ;
- `VITE_DOCS_URL`.

## Services production

Les services déclarés sont :

- `database` ;
- `phpmyadmin` ;
- `apache` ;
- `front` ;
- `documentation`.

Les ports sont pilotés par les variables d'environnement.

## Volumes critiques

Les volumes critiques concernent :

- la base MariaDB ;
- les documents uploadés ;
- les clés JWT.

Ces emplacements doivent être sauvegardés et conservés entre les déploiements.

## Pré-production

Le job `deploy-pre-prod` est exécuté lorsque la branche `pre-prod` est déployée.

Il appelle l'API Dokploy de l'environnement de pré-production avec les secrets
GitHub correspondants.

## Production

Le job `deploy-prod` est exécuté lors d'un push de tag.

Il appelle l'API Dokploy de production avec les secrets GitHub correspondants.

## Checklist avant déploiement

- CI verte ;
- Quality Gate SonarQube validée ;
- migrations Doctrine vérifiées ;
- variables d'environnement à jour ;
- volumes documents et JWT présents ;
- URL frontend/API/docs cohérentes ;
- sauvegarde récente disponible.
