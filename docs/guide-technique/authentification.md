# Authentification

L'authentification repose sur JWT.

Le frontend stocke le jeton côté client et l'ajoute automatiquement aux requêtes
API. En cas de réponse `401`, l'utilisateur est redirigé vers la page de
connexion.

Les permissions côté interface ne remplacent pas les contrôles côté API.

## Connexion API

La route de connexion est :

```text
POST /api/login
```

Le firewall `login` attend un JSON avec :

```json
{
  "email": "utilisateur@example.com",
  "password": "mot-de-passe"
}
```

En cas de succès, LexikJWTAuthenticationBundle renvoie un token JWT.

## Firewalls Symfony

La configuration distingue :

- `login` pour `/api/login` ;
- `api` pour les routes `/api` protégées par JWT ;
- `main` pour les routes web Symfony ;
- `dev` pour les assets/profiler en développement.

## Routes publiques

Les routes publiques sont notamment :

- `/api/login` ;
- `/api/reset-password` ;
- `/api/docs` ;
- `/login` ;
- `/reset-password`.

Les autres routes API nécessitent un utilisateur authentifié.

## Rôles

Les rôles principaux sont :

- `ROLE_USER` ;
- `ROLE_ADMIN`.

Les contrôles frontend servent à masquer ou désactiver des actions, mais la
source de vérité reste l'API.

## Réinitialisation du mot de passe

Le projet utilise SymfonyCasts ResetPasswordBundle.

Le frontend expose :

- `/reset-password/request` pour demander un lien ;
- `/reset-password/reset/:token` pour définir le nouveau mot de passe.

La configuration mailer doit être valide pour envoyer les emails.
