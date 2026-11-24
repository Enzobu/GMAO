# Stock

Le stock est organisé par **type de pièce** et par **compatibilité véhicule**, sans notion obligatoire de marque ou de référence fabricant. Les types sont gérés dans **Configuration**.

## Quantité partagée

Une ligne de stock dispose d'une quantité unique, même si elle est compatible avec plusieurs véhicules.

Par exemple, cinq flacons de liquide de frein compatibles avec la Focus, la Polo et la Ninja représentent **cinq flacons au total**, pas cinq par véhicule.

Un type de pièce ne peut pas figurer sur deux lignes de stock qui couvrent un même véhicule. Si une ligne correspond déjà à la pièce recherchée, ouvrez-la et utilisez **Ajouter** pour la réapprovisionner.

## Consultation

La page Stock propose :

- **Par véhicule** : affiche les pièces compatibles avec le véhicule sélectionné, y compris celles en rupture ;
- **Vue globale** : affiche les lignes de stock de tous les véhicules ;
- une recherche textuelle, des filtres par type, statut et véhicule, des tris et une pagination ;
- une présentation en tableau avec quantité, compatibilités et actions rapides.

Les résultats se mettent à jour après un court délai de saisie. Un état de chargement indique que la recherche est en cours.

## Statuts

- **Rupture** : 0 unité ;
- **Stock faible** : 1 unité ;
- **OK** : 2 unités ou davantage.

Les lignes à zéro restent dans le stock afin d'être réapprovisionnées.

## Gestion

Les administrateurs peuvent créer, modifier et supprimer des lignes et ajouter des quantités. Les autres utilisateurs disposent d'un accès en lecture.

À la création ou à la modification, l'application refuse les doublons de type de pièce qui partagent au moins un véhicule compatible. Si le formulaire rencontre une ligne déjà existante, il propose un lien permettant de la consulter et de la réapprovisionner.

Une ligne jamais utilisée peut être supprimée définitivement. Une ligne portant un historique d'intervention ou des documents est masquée de la consultation active afin de préserver ses références.

## Utilisation dans les interventions

Les pièces ne sont consommées **qu'à la clôture** d'une intervention. Le stock est ajusté par différence lorsqu'une intervention terminée est modifiée. Une intervention déplanifiée ou supprimée restitue les quantités qu'elle avait consommées.

Les pièces indisponibles ne peuvent pas être ajoutées à une nouvelle intervention. Dans une intervention terminée existante, une pièce déjà consommée reste modifiable, même si la quantité actuelle en stock est nulle.

Voir [Interventions](./interventions.md).
