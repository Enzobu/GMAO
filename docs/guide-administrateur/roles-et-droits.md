# Rôles et droits

Les rôles déterminent les pages visibles et les actions autorisées.

Attribuez toujours le droit le plus limité compatible avec le besoin réel de
l'utilisateur.

Après modification d'un rôle, demandez à l'utilisateur de se reconnecter si les
changements ne sont pas visibles immédiatement.

## Rôles disponibles

L'interface utilise deux rôles principaux :

- **Utilisateur** (`ROLE_USER`) ;
- **Administrateur** (`ROLE_ADMIN`).

Le rôle utilisateur est obligatoire et reste toujours coché dans le formulaire
utilisateur.

## Droits utilisateur

Un utilisateur standard peut :

- consulter les véhicules accessibles ;
- modifier les véhicules dont il est propriétaire ;
- gérer les assurances, contrôles et interventions de ses véhicules ;
- consulter le stock ;
- ajouter ou modifier des documents lorsqu'il a le droit de gestion sur la
  ressource ;
- modifier son profil ;
- demander une réinitialisation de mot de passe.

## Droits administrateur

Un administrateur peut en plus :

- créer et modifier tous les utilisateurs ;
- attribuer ou retirer le rôle administrateur ;
- archiver les utilisateurs ;
- créer, modifier et archiver les véhicules ;
- gérer le stock ;
- supprimer/archiver les ressources métier ;
- archiver les documents ;
- accéder à la configuration ;
- forcer certains enregistrements après alerte métier.

## Lecture seule

Quand un utilisateur voit une ressource sans pouvoir la modifier, l'interface
affiche généralement un badge **Lecture seule**.

Ce cas peut apparaître pour :

- véhicules non possédés ;
- utilisateurs non modifiables ;
- stock pour les non-administrateurs ;
- assurances, contrôles ou interventions d'un véhicule non possédé.

## Modification des rôles

Pour modifier les rôles :

1. Ouvrez **Utilisateurs**.
2. Ouvrez la fiche du compte.
3. Cliquez sur **Modifier**.
4. Cochez ou décochez **Administrateur**.
5. Enregistrez.

Le rôle **Utilisateur** ne peut pas être retiré depuis l'interface.

## Recommandations

- Limiter le nombre de comptes administrateurs.
- Ne pas utiliser un compte administrateur pour des usages courants si ce n'est
  pas nécessaire.
- Contrôler régulièrement les comptes ayant le rôle administrateur.
- Retirer le rôle administrateur dès qu'il n'est plus nécessaire.
