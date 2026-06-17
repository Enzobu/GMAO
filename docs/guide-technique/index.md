# Guide technique

Ce guide aide les développeurs et mainteneurs à comprendre, installer et faire
évoluer le projet GMAO.

Le projet est composé d'une API Symfony/API Platform et d'un frontend
React/Vite/TypeScript.

## Objectifs

Cette section documente :

- l'architecture générale ;
- l'installation locale ;
- les variables d'environnement ;
- les services Docker ;
- les conventions API ;
- les conventions frontend ;
- l'authentification ;
- les uploads de documents ;
- les tests et la qualité ;
- le déploiement.

## Dossiers principaux

```text
api/       API Symfony 7.2, API Platform 4, Doctrine, JWT
frontend/  Application React 19, Vite, TypeScript
docs/      Documentation VitePress
```

## Commandes de base

Depuis la racine :

```bash
make build
make up
make exec
make down
```

Frontend :

```bash
cd frontend
npm install
npm run dev
npm run build
```

Documentation :

```bash
cd docs
npm install
npm run dev
npm run build
```

## Principes de maintenance

- Garder les changements simples et testés.
- Ajouter ou adapter les tests API pour chaque changement backend.
- Vérifier le frontend avec `npm run build` après modification.
- Ne jamais documenter de valeurs réelles issues des fichiers `.env*`.
- Servir les documents uploadés via des requêtes authentifiées.
