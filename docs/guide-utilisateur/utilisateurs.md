# Utilisateurs

La page **Utilisateurs** est visible par tous les comptes connectés.

Elle permet de consulter l'annuaire des utilisateurs, d'ouvrir une fiche et de
retrouver rapidement un compte par recherche ou filtre.

## Liste des utilisateurs

La liste affiche les comptes sous forme de cartes.

Chaque carte contient :

- les initiales ;
- le nom affiché ;
- les rôles ;
- l'email ;
- un badge **Vous** pour le compte connecté ;
- un badge **Lecture seule** si le compte n'est pas modifiable par
  l'utilisateur courant.

![Liste des utilisateurs](/images/captures/utilisateurs-liste.png)

## Filtres disponibles

La liste propose :

- une recherche par nom, email ou rôle ;
- un filtre rôle : tous, administrateurs, utilisateurs ;
- un filtre droit : tous, modifiables, lecture seule ;
- un tri : nom, email ou rôle ;
- une pagination avec choix du nombre d'éléments par page.

## Fiche utilisateur

La fiche utilisateur affiche :

- email ;
- identifiant interne ;
- prénom ;
- nom ;
- adresse ;
- documents associés.

Les badges indiquent les rôles et le compte courant.

![Fiche utilisateur](/images/captures/utilisateur-detail.png)

## Modifier un utilisateur

Les droits de modification dépendent du profil connecté :

- un utilisateur standard peut modifier son propre profil via la page
  **Profil** ;
- un administrateur peut modifier tous les comptes depuis la liste ou la fiche
  utilisateur.

Les informations modifiables par un administrateur sont :

- prénom ;
- nom ;
- email ;
- rôles ;
- adresse ;
- documents associés depuis la fiche.

![Formulaire utilisateur](/images/captures/utilisateur-formulaire.png)

## Créer un utilisateur

La création est réservée aux administrateurs.

Depuis la liste, cliquez sur **Ajouter un utilisateur**.

Champs obligatoires :

- prénom ;
- nom ;
- email ;
- adresse ;
- code postal ;
- ville ;
- pays.

Le rôle **Utilisateur** est toujours présent. Le rôle **Administrateur** peut
être coché si le compte doit accéder aux fonctions d'administration.

À la création, l'application indique qu'un email de définition du mot de passe
sera envoyé à l'utilisateur.

Des documents peuvent être attachés au compte lors de la création.

## Archiver un utilisateur

L'action **Supprimer** est réservée aux administrateurs.

Elle archive l'utilisateur et le masque de la plateforme sans supprimer
physiquement ses données.

Un administrateur ne peut pas supprimer son propre compte administrateur depuis
l'interface.

![Confirmation de suppression utilisateur](/images/captures/admin-utilisateur-suppression.png)

## Bonnes pratiques

- Éviter les comptes partagés.
- Vérifier l'adresse email avant création.
- Attribuer le rôle administrateur uniquement aux personnes qui en ont besoin.
- Archiver les comptes qui ne doivent plus accéder à l'application.
