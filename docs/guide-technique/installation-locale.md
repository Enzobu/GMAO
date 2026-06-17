# Installation locale

Cette page décrit l'installation d'un environnement de développement.

## Prérequis

- Docker et Docker Compose ;
- Node.js compatible avec le frontend ;
- npm ;
- Git ;
- accès aux fichiers d'environnement nécessaires.

Les fichiers `.env*` réels contiennent des valeurs sensibles. Ne les copiez pas
dans la documentation et ne les partagez pas.

## Démarrer l'API et la base

Depuis la racine du projet :

```bash
make build
make up
```

Les services lancés en développement sont notamment :

- `database` ;
- `apache` ;
- `phpmyadmin`.

L'API est servie par le conteneur `apache`.

## Entrer dans le conteneur API

```bash
make exec
```

Les commandes Symfony, Composer et PHPUnit doivent être exécutées dans ce
conteneur.

## Initialiser ou réinitialiser la base locale

La commande `mep` réinstalle les dépendances, reconstruit le schéma, charge les
fixtures et vide le cache.

```bash
make mep
```

Cette commande est destinée au développement uniquement. Elle ne doit pas être
lancée en production.

## Installer et lancer le frontend

Pour installer le frontend :

```bash
cd frontend
npm install
```

Pour lancer Vite :

```bash
npm run dev
```

Pour vérifier le build :

```bash
npm run build
```

## Installer et lancer la documentation

Pour lancer la documentation :

```bash
cd docs
npm install
npm run dev
```

Pour vérifier le build :

```bash
npm run build
```

## Arrêter l'environnement

```bash
make down
```

Pour supprimer les volumes de conteneurs, utilisez uniquement les commandes de
cleanup prévues et après avoir vérifié que les données peuvent être perdues.
