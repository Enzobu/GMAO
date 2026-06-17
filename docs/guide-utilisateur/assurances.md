# Assurances

Les assurances sont rattachées à un véhicule.

Elles permettent de suivre les contrats, les périodes de validité et les
documents associés.

## Accéder aux assurances

Depuis une fiche véhicule, cliquez sur **Voir les assurances**.

La liste affiche :

- l'assureur ;
- le statut actif ou inactif ;
- la fréquence de paiement ;
- le numéro de police ;
- la date de début ;
- la date de fin ;
- la date de dernière mise à jour.

![Liste des assurances](/images/captures/assurances-liste.png)

## Statut actif

Une assurance est considérée comme active si :

- l'API indique explicitement qu'elle est active ;
- ou aucune date de fin n'est renseignée ;
- ou la date de fin est postérieure à la date du jour.

La fiche véhicule affiche un avertissement si aucune assurance active n'est
présente.

## Ajouter une assurance

Depuis la liste des assurances du véhicule, cliquez sur **Ajouter**.

Champs disponibles :

- assureur, obligatoire ;
- numéro de police ;
- date de début ;
- date de fin ;
- fréquence de paiement : mensuel ou annuel ;
- documents, uniquement à la création.

![Formulaire assurance](/images/captures/assurance-formulaire.png)

## Clôturer l'assurance active précédente

Lors de l'ajout d'une nouvelle assurance, si une assurance active existe déjà
pour le véhicule, l'application propose de la clôturer.

Vous pouvez saisir la date de fin de l'assurance actuelle puis confirmer.

![Clôture de l'assurance active](/images/captures/cloture-assurance-active.png)

## Modifier une assurance

Une assurance peut être modifiée par le propriétaire du véhicule ou par un
administrateur.

Les documents déjà associés se gèrent depuis la fiche assurance.

## Supprimer une assurance

L'action de suppression masque l'assurance de la plateforme.

Elle est réservée aux administrateurs.
