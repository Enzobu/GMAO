# Véhicules

Les véhicules représentent les éléments du parc suivis dans la GMAO.

La section **Véhicules** permet de consulter le parc, créer ou modifier un
véhicule selon les droits, accéder à l'historique, aux assurances, aux contrôles
techniques, aux interventions et aux documents.

## Liste des véhicules

La liste affiche des cartes avec :

- le nom du véhicule ;
- le type ;
- le statut ;
- l'immatriculation ;
- l'année ;
- le dernier kilométrage ;
- le carburant ;
- la transmission ;
- la couleur ;
- un badge **Lecture seule** si vous ne pouvez pas modifier le véhicule.

## Filtres disponibles

Vous pouvez filtrer la liste par :

- recherche texte : nom, immatriculation, marque ;
- type de véhicule ;
- statut ;
- droit : tous, modifiables, lecture seule ;
- tri : nom, immatriculation, année récente, kilométrage décroissant.

Le bouton de réinitialisation remet tous les filtres à leur valeur par défaut.

![Liste des véhicules](/images/captures/vehicules-liste.png)

## Statuts de véhicule

Les statuts disponibles sont :

- **Actif** ;
- **Vendu** ;
- **Archivé** ;
- **Inactif** ;
- **Hors service**.

## Créer un véhicule

Depuis la liste, cliquez sur **Ajouter un véhicule**.

Les informations obligatoires sont :

- nom ;
- immatriculation ;
- marque ;
- modèle ;
- statut ;
- couleur ;
- kilométrage.

Les champs complémentaires permettent de renseigner :

- type ;
- année ;
- VIN ;
- moteur ;
- carburant ;
- transmission ;
- date d'achat ;
- prix d'achat.

Les administrateurs peuvent aussi choisir le propriétaire du véhicule.

![Formulaire véhicule](/images/captures/vehicule-formulaire.png)

## Modifier un véhicule

Un véhicule peut être modifié par :

- son propriétaire ;
- un administrateur.

En modification, un utilisateur non administrateur ne peut pas modifier le
kilométrage directement depuis la fiche véhicule. Le kilométrage est mis à jour
par les opérations métier, notamment les interventions terminées et les
contrôles techniques.

## Fiche véhicule

La fiche véhicule regroupe :

- identité : nom, marque, modèle, immatriculation, type et statut ;
- caractéristiques : année, VIN, moteur, carburant, transmission, couleur ;
- achat et suivi : date d'achat, prix, kilométrage, propriétaire ;
- dernière assurance ;
- dernier contrôle technique ;
- dernière intervention réalisée ;
- documents associés.

Depuis cette fiche, vous pouvez accéder aux listes détaillées :

- **Voir les assurances** ;
- **Voir les contrôles** ;
- **Voir les interventions** ;
- **Ajouter une intervention** si vous avez les droits.

![Fiche véhicule](/images/captures/vehicule-detail.png)

## Télécharger l'historique

Le bouton **Télécharger l'historique** génère une archive `.zip` contenant
l'historique complet du véhicule.

Le nom du fichier reprend le nom du véhicule et son immatriculation.

## Archiver un véhicule

L'action **Supprimer** archive le véhicule. Elle ne supprime pas définitivement
les données.

Cette action est réservée aux administrateurs.
