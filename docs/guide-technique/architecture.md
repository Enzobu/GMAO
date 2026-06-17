# Architecture

Le projet est organisé autour de deux applications principales :

- `api/` : API Symfony 7.2 avec API Platform 4 ;
- `frontend/` : application React 19 avec Vite et TypeScript.

L'API expose les ressources métier sous le préfixe `/api`. Le frontend consomme
ces ressources via un client HTTP qui ajoute le jeton Bearer lorsque
l'utilisateur est connecté.

## Vue d'ensemble

```text
Navigateur
  -> frontend React/Vite
  -> API Symfony/API Platform
  -> MariaDB
  -> stockage documents
```

En local, l'API et la base de données sont lancées via Docker Compose. Le
frontend peut être lancé séparément avec Vite.

En production, les services Docker principaux sont :

- `database` ;
- `apache` ;
- `front` ;
- `documentation` ;
- `phpmyadmin` si exposé.

## API

Le dossier `api/` contient :

- `src/Entity/` : entités Doctrine et ressources API Platform ;
- `src/ApiPlatform/State/` : processors métier ;
- `src/Repository/` : accès aux données ;
- `src/Security/` : expressions et règles de sécurité ;
- `src/Service/` : logique métier transverse ;
- `src/DataFixtures/` : données de développement/test ;
- `tests/` : tests automatisés.

Les entités principales sont :

- `User` ;
- `Vehicle` ;
- `VehicleInsurance` ;
- `VehicleInspection` ;
- `Maintenance` ;
- `MaintenancePart` ;
- `Part` ;
- `PartType` ;
- `MaintenanceType` ;
- `InspectionCenter` ;
- `Document` ;
- `Address`.

## Frontend

Le dossier `frontend/` contient :

- `src/api/` : clients HTTP par domaine ;
- `src/pages/` : pages applicatives ;
- `src/components/` : composants réutilisables ;
- `src/router/` : routes React ;
- `src/stores/` : stores Zustand ;
- `src/types/` : types TypeScript ;
- `src/lib/` : helpers métier et formatage.

Les imports `@/...` pointent vers `frontend/src`.

## Documentation

Le dossier `docs/` contient le site VitePress.

Il est indépendant de l'application frontend métier et peut être publié ou
servi séparément.

## Données et sécurité

Les ressources API sont protégées par JWT. Les routes API sont accessibles sous
`/api`, sauf les routes explicitement publiques comme la connexion et la
réinitialisation de mot de passe.

Les ressources métiers utilisent souvent une suppression logique. Les données
masquées restent présentes en base et ne doivent pas être supprimées à la main
sans analyse des dépendances.
