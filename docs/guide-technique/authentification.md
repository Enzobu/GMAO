# Authentification

L'authentification repose sur JWT.

Le frontend stocke le jeton côté client et l'ajoute automatiquement aux requêtes
API. En cas de réponse `401`, l'utilisateur est redirigé vers la page de
connexion.

Les permissions côté interface ne remplacent pas les contrôles côté API.
