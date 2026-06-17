# Données et suppression

Certaines données peuvent être supprimées logiquement plutôt que physiquement.

Une suppression logique masque la donnée dans l'application tout en conservant
une trace côté base de données.

Avant de supprimer une donnée, vérifiez les dépendances avec les interventions,
documents ou historiques associés.

## Ressources archivables

Les administrateurs peuvent archiver ou supprimer logiquement plusieurs types de
données :

- utilisateurs ;
- véhicules ;
- assurances ;
- contrôles techniques ;
- interventions ;
- lignes de stock ;
- documents ;
- référentiels de configuration selon les dépendances.

## Effet d'une suppression logique

Une suppression logique :

- masque la donnée dans les listes courantes ;
- conserve l'information en base ;
- préserve l'historique et les liens nécessaires ;
- peut laisser la donnée visible sur des historiques existants.

L'interface utilise parfois le mot **Supprimer**, mais les messages de
confirmation indiquent que la donnée sera masquée ou archivée.

## Cas particuliers

### Utilisateurs

Un utilisateur archivé ne doit plus être utilisé pour accéder à l'application.

L'interface empêche un administrateur de supprimer son propre compte
administrateur.

### Véhicules

Un véhicule archivé est masqué de la plateforme. Les données associées restent
conservées pour l'historique.

### Interventions

Si une intervention réalisée est supprimée, les pièces consommées sont
restaurées dans le stock.

### Documents

Un document archivé est masqué de la ressource. Le fichier n'est plus proposé
dans le panneau Documents.

![Actions administrateur sur les documents](/images/captures/admin-document-actions.png)

### Référentiels

Un type de pièce ou un centre de contrôle peut être conservé sur les données
existantes même s'il n'est plus proposé dans les formulaires.

La suppression d'un type de pièce peut être refusée si des pièces l'utilisent
encore.

## Avant d'archiver

Vérifiez :

- que la donnée n'est plus utilisée opérationnellement ;
- qu'il ne s'agit pas d'un doublon à fusionner manuellement ;
- que les documents importants ont été récupérés si nécessaire ;
- que l'action ne bloque pas un utilisateur ou un processus métier.

## Après archivage

Contrôlez que :

- la donnée a disparu des listes attendues ;
- les données liées restent cohérentes ;
- le stock est correct si une intervention a été supprimée ;
- les utilisateurs concernés sont informés si nécessaire.
