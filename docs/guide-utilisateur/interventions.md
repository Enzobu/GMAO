# Interventions

Les interventions permettent de suivre les opérations prévues, en cours,
terminées ou annulées sur les véhicules.

Elles peuvent consommer des pièces du stock, mettre à jour le kilométrage et
porter des documents justificatifs.

## Accès aux interventions

Il existe deux accès :

- **Interventions** dans la barre latérale : liste globale en lecture ;
- **Voir les interventions** depuis une fiche véhicule : liste filtrée sur ce
  véhicule, avec actions si vous avez les droits.

## Liste des interventions

La liste affiche les interventions avec :

- le type d'intervention ;
- le véhicule concerné ;
- le statut ;
- les dates prévues, de début et de fin ;
- le kilométrage ;
- le mode interne ou externe ;
- les prochaines échéances ;
- les actions rapides selon le statut.

![Liste des interventions](/images/captures/interventions-liste.png)

## Filtres

Vous pouvez filtrer les interventions avec :

- une recherche texte ;
- un statut.

Les statuts disponibles sont :

- **À faire** ;
- **En cours** ;
- **Terminé** ;
- **Annulé**.

## Créer une intervention

Depuis la fiche d'un véhicule, ouvrez **Voir les interventions**, puis cliquez
sur **Ajouter**.

Champs disponibles :

- type d'intervention, obligatoire ;
- statut, obligatoire ;
- kilométrage, obligatoire uniquement si l'intervention est terminée ;
- mode : interne ou externe ;
- date prévue ;
- date de début ;
- date de fin, obligatoire si l'intervention est terminée ;
- prochaine échéance kilométrique ;
- prochaine échéance date ;
- notes ;
- pièces utilisées ;
- documents, uniquement à la création.

![Formulaire intervention](/images/captures/intervention-formulaire.png)

## Démarrer rapidement une intervention

Depuis la liste des interventions d'un véhicule, le bouton **Démarrer** est
visible pour les interventions au statut **À faire**.

Il permet de passer l'intervention au statut **En cours** en renseignant la date
de début.

## Terminer rapidement une intervention

Le bouton **Terminer** est visible pour les interventions au statut **En cours**.

Il permet de passer l'intervention au statut **Terminé** en renseignant :

- la date de fin ;
- le kilométrage.

## Pièces utilisées

Une intervention peut consommer des pièces du stock.

Seules les pièces compatibles avec le véhicule sont proposées.

Pour chaque pièce, renseignez :

- la pièce ;
- la quantité.

Si aucune pièce compatible n'existe pour le véhicule, l'interface affiche un
message et empêche l'ajout de ligne de pièce.

## Impact sur le stock

Lorsqu'une intervention est enregistrée comme terminée, les pièces utilisées
sont consommées dans le stock.

Si une intervention déjà réalisée est repassée dans un état non terminé ou perd
sa date de réalisation, l'application demande confirmation pour restaurer les
pièces en stock.

## Alerte kilométrage

Si le kilométrage saisi entre en conflit avec le kilométrage connu du véhicule,
une alerte peut apparaître.

Un administrateur peut forcer l'enregistrement si la valeur est volontaire.

![Alerte kilométrage](/images/captures/alerte-kilometrage.png)

## Fiche intervention

La fiche intervention affiche :

- le véhicule ;
- les badges de statut et mode ;
- le kilométrage ;
- les dates ;
- les prochaines échéances ;
- les pièces utilisées ;
- les notes ;
- les documents associés.

## Suppression

La suppression d'une intervention masque l'intervention de la plateforme.

Si l'intervention était réalisée, les pièces consommées sont restaurées.

Cette action est réservée aux administrateurs.
