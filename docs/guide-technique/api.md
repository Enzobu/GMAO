# API

L'API est basée sur Symfony et API Platform.

Les routes API Platform sont préfixées par `/api`. La route de connexion JWT est
`/api/login`.

Les entités exposées utilisent des groupes de sérialisation et peuvent être
complétées par des state processors pour gérer les règles métier.

## Stack

- PHP `>=8.2` ;
- Symfony `7.2` ;
- API Platform `4` ;
- Doctrine ORM ;
- MariaDB ;
- LexikJWTAuthenticationBundle ;
- SymfonyCasts ResetPasswordBundle ;
- PHPUnit `12`.

## Ressources métier

Les principales ressources API sont :

- utilisateurs ;
- véhicules ;
- assurances véhicule ;
- contrôles techniques ;
- interventions ;
- pièces utilisées sur intervention ;
- stock ;
- types de pièces ;
- types d'entretiens ;
- centres de contrôle technique ;
- documents ;
- adresses.

## Sécurité des opérations

Les opérations API Platform déclarent leurs règles de sécurité au niveau des
entités.

Schéma général :

- lecture : utilisateurs connectés ;
- création/modification métier : utilisateur autorisé ou administrateur selon
  la ressource ;
- suppression : souvent réservée à l'administrateur ;
- configuration : administrateur.

Les expressions de sécurité sont centralisées dans le code PHP, notamment pour
les règles du type administrateur ou propriétaire.

## State processors

Les processors dans `api/src/ApiPlatform/State/` portent la logique métier
associée aux écritures.

Exemples de responsabilités :

- suppression logique ;
- validation de droits métier ;
- gestion des effets de bord sur le stock ;
- gestion des règles liées aux véhicules ;
- formatage ou enrichissement avant persistance.

Avant de modifier une entité exposée, vérifiez toujours son processor.

## Pagination et formats

API Platform est configuré avec :

- pagination par défaut à 12 éléments ;
- pagination client autorisée ;
- maximum à 36 éléments ;
- formats JSON et JSON-LD.

## Documentation API

La documentation API Platform est publique sous `/api/docs`.

L'accès aux autres routes `/api` nécessite un JWT valide, sauf les routes
explicitement publiques.
