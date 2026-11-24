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

La section **Pièces utilisées** dispose d'une recherche par nom de pièce.
Elle ne propose à l'ajout que les pièces **compatibles avec le véhicule** dont
la quantité disponible est strictement positive.

- Sélectionnez une pièce : elle apparaît dans la liste des pièces choisies.
- La pièce sélectionnée disparaît aussitôt des résultats pour empêcher un doublon.
- Ajustez la quantité utilisée, dans la limite du stock disponible.
- Retirez une pièce pour la rendre de nouveau disponible dans la recherche.

Les pièces d'une intervention déjà terminée restent visibles en édition même
si leur stock actuel vaut zéro. Le maximum autorisé tient alors compte des
quantités consommées par cette intervention.

## Impact sur le stock

Une intervention **À faire** ou **En cours** ne consomme et ne réserve aucune
pièce. À son passage au statut **Terminé**, une fenêtre confirme les débits.

Si vous éditez une intervention déjà terminée, la fenêtre ne s'affiche que
lorsqu'une quantité consommée change. Le stock n'est ajusté que de la
**différence** :

- ajout de quatre bougies : débit de quatre ;
- passage de deux à quatre bougies : débit de deux ;
- passage de quatre à deux : restitution de deux ;
- suppression d'une ligne : restitution de la quantité précédemment utilisée ;
- modification de notes ou kilométrage : aucun mouvement.

Une intervention terminée peut recevoir de nouvelles pièces ultérieurement :
elles sont débitées lors de l'enregistrement de cette modification.

Le retour à un statut non terminé ou la suppression d'une intervention terminée
restitue les quantités qu'elle avait consommées.

Une confirmation est demandée pour les mouvements de stock. Le serveur vérifie
aussi que la quantité demandée est disponible, y compris si elle a changé
depuis l'ouverture du formulaire.

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
