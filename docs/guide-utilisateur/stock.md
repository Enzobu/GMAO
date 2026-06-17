# Stock

La section **Stock** liste les pièces disponibles et leurs compatibilités avec
les véhicules.

Les utilisateurs peuvent consulter le stock. Les administrateurs peuvent créer,
modifier, supprimer et ajouter des quantités.

## Liste du stock

Chaque carte de stock affiche :

- le type de pièce ;
- la quantité ;
- la date de mise à jour ;
- une note éventuelle ;
- les véhicules compatibles ;
- le statut de stock ;
- un badge **Lecture seule** pour les utilisateurs non administrateurs.

## Statuts de stock

Les statuts sont calculés à partir de la quantité :

- **OK** : quantité supérieure à 1 ;
- **Stock faible** : quantité égale à 1 ;
- **Rupture** : quantité égale à 0.

## Filtres disponibles

Vous pouvez filtrer par :

- recherche texte : pièce, note, véhicule ;
- véhicule compatible ;
- type de pièce ;
- statut de stock ;
- tri : quantité croissante, quantité décroissante, nom, mise à jour.

## Compatibilité véhicule

Une pièce doit être compatible avec un véhicule pour être proposée dans le
formulaire d'intervention de ce véhicule.

Si une pièce n'a aucun véhicule compatible, elle apparaît avec un badge
**Aucun véhicule compatible** et ne pourra pas être utilisée dans une
intervention.

## Ajouter une ligne de stock

Action réservée aux administrateurs.

Champs disponibles :

- type de pièce, obligatoire ;
- quantité, obligatoire ;
- note ;
- véhicules compatibles ;
- documents, uniquement à la création.

## Ajouter de la quantité

Depuis la liste, un administrateur peut cliquer sur **Ajouter stock**.

La fenêtre indique le stock actuel et demande le nombre de pièces à ajouter.

La quantité saisie doit être strictement positive.

## Fiche stock

La fiche stock affiche :

- le type de pièce ;
- la quantité ;
- la note ;
- les véhicules compatibles ;
- les dates de création et de mise à jour ;
- les documents associés.

## Suppression

La suppression masque la ligne de stock de la plateforme.

Elle est réservée aux administrateurs.
