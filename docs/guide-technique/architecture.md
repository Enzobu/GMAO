# Architecture

Le projet est organisé autour de deux applications principales :

- `api/` : API Symfony 7.2 avec API Platform 4 ;
- `frontend/` : application React 19 avec Vite et TypeScript.

L'API expose les ressources métier sous le préfixe `/api`. Le frontend consomme
ces ressources via un client HTTP qui ajoute le jeton Bearer lorsque
l'utilisateur est connecté.
