# Utilisateurs

Les administrateurs peuvent créer, modifier et archiver des comptes
utilisateurs.

Avant de créer un compte, vérifiez le périmètre de l'utilisateur et le rôle à
attribuer.

## Liste des utilisateurs

La page **Utilisateurs** affiche les comptes sous forme de cartes.

Chaque carte contient :

- les initiales ;
- le nom affiché ;
- les rôles ;
- l'email ;
- un badge **Vous** pour le compte connecté ;
- un badge **Lecture seule** si le compte n'est pas modifiable par
  l'utilisateur courant.

## Filtres disponibles

La liste propose :

- une recherche par nom, email ou rôle ;
- un filtre rôle : tous, administrateurs, utilisateurs ;
- un filtre droit : tous, modifiables, lecture seule ;
- un tri : nom, email ou rôle ;
- une pagination avec choix du nombre d'éléments par page.

## Créer un utilisateur

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

## Modifier un utilisateur

Un administrateur peut modifier n'importe quel compte depuis la fiche
utilisateur ou la liste.

Les informations modifiables sont :

- prénom ;
- nom ;
- email ;
- rôles ;
- adresse ;
- documents associés depuis la fiche.

Un utilisateur non administrateur peut uniquement modifier son propre profil via
la page **Profil**.

## Fiche utilisateur

La fiche utilisateur affiche :

- email ;
- identifiant interne ;
- prénom ;
- nom ;
- adresse ;
- documents associés.

Les badges indiquent les rôles et le compte courant.

## Archiver un utilisateur

L'action **Supprimer** archive l'utilisateur et le masque de la plateforme.

Cette action ne supprime pas physiquement les données.

Un administrateur ne peut pas supprimer son propre compte administrateur depuis
l'interface. L'application affiche un message d'action impossible.

## Bonnes pratiques

- Éviter les comptes partagés.
- Attribuer le rôle administrateur uniquement aux personnes qui en ont besoin.
- Vérifier l'adresse email avant création, car elle sert à la connexion et à la
  réinitialisation du mot de passe.
- Archiver les comptes qui ne doivent plus accéder à l'application.
