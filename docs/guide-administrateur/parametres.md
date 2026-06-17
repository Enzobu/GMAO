# Configuration

La page **Configuration** permet de gérer les référentiels utilisés par les
formulaires métier.

Elle est accessible uniquement aux administrateurs depuis la barre latérale.

## Référentiels disponibles

La page contient trois panneaux :

- **Types d'entretiens** ;
- **Types de pièces** ;
- **Centres de contrôle technique**.

Chaque panneau affiche un compteur, une recherche et un bouton **Ajouter**.

## Types d'entretiens

Les types d'entretiens sont utilisés dans les interventions.

Exemples : vidange, pneus, freinage, révision, distribution.

Champs disponibles :

- nom, obligatoire ;
- description.

Lorsqu'un type est supprimé, il n'est plus proposé dans les formulaires, mais il
reste visible sur les interventions existantes.

## Types de pièces

Les types de pièces structurent le stock.

Exemples : filtre à huile, pneu, plaquette de frein, batterie.

Champs disponibles :

- nom, obligatoire ;
- description.

La suppression peut être refusée si des lignes de stock utilisent encore ce
type.

## Centres de contrôle technique

Les centres sont proposés dans le formulaire de contrôle technique.

Champs disponibles :

- nom, obligatoire ;
- téléphone ;
- email ;
- adresse, obligatoire ;
- complément ;
- code postal, obligatoire ;
- ville, obligatoire ;
- pays, obligatoire.

Le téléphone est formaté automatiquement sur 10 chiffres. Le code postal accepte
uniquement des chiffres et est limité à 5 caractères.

Quand un centre est supprimé, il n'est plus proposé dans les formulaires, mais
reste visible sur les contrôles techniques existants.

## Recherche

Chaque panneau possède sa propre recherche :

- nom ou description pour les types d'entretiens et types de pièces ;
- nom, contact ou adresse pour les centres de contrôle technique.

Le compteur affiche le nombre d'éléments filtrés lorsque la recherche est
active.

## Statuts

Les éléments peuvent apparaître avec un badge :

- **Actif** ;
- **Supprimé**.

Les éléments supprimés ne proposent plus les actions de modification ou de
suppression.

## Bonnes pratiques

- Vérifier qu'un référentiel n'existe pas déjà avant de le créer.
- Utiliser des noms courts et homogènes.
- Renseigner les descriptions quand elles clarifient l'usage métier.
- Éviter de supprimer un référentiel utilisé par des données existantes.
