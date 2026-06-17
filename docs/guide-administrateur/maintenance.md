# Maintenance

Les opérations de maintenance doivent être réalisées avec prudence, idéalement
sur une période de faible activité.

Points à vérifier régulièrement :

- disponibilité de l'application ;
- espace disque des documents ;
- sauvegardes ;
- état des traitements ou logs d'erreur ;
- validité des accès administrateurs.

## Contrôles réguliers

À intervalle régulier, vérifiez :

- que les administrateurs actifs sont toujours légitimes ;
- que les comptes inactifs sont archivés ;
- que les référentiels de configuration ne contiennent pas de doublons ;
- que les types d'entretiens et de pièces restent cohérents ;
- que les centres de contrôle technique sont à jour ;
- que les documents ne saturent pas l'espace disque ;
- que les sauvegardes sont disponibles et restaurables.

## Documents

Les documents uploadés sont un point sensible de maintenance.

Surveillez :

- le volume total du répertoire de documents ;
- les fichiers volumineux ;
- les documents attachés à des ressources archivées ;
- la capacité à télécharger ou prévisualiser les documents.

## Stock

Contrôlez régulièrement les lignes de stock :

- pièces en rupture ;
- pièces en stock faible ;
- pièces sans véhicule compatible ;
- écarts après suppression ou modification d'interventions réalisées.

## Référentiels

Les référentiels doivent rester simples et lisibles.

Avant d'ajouter un nouveau type, cherchez les doublons ou variantes proches.

Exemples à éviter :

- `Vidange` et `Vidange moteur` si les deux désignent la même opération ;
- `Pneu`, `Pneus` et `Pneumatique` pour le même type de pièce.

## Accès administrateurs

Revoyez périodiquement les comptes ayant le rôle administrateur.

Retirez le rôle dès qu'il n'est plus nécessaire et archivez les comptes qui ne
doivent plus accéder à l'application.

## En cas d'incident

Collectez les informations suivantes avant diagnostic :

- utilisateur concerné ;
- action réalisée ;
- ressource concernée ;
- message affiché ;
- heure approximative ;
- navigateur utilisé ;
- présence éventuelle d'un document ou d'un fichier uploadé.
