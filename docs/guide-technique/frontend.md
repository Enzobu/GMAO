# Frontend

Le frontend est une application React 19, Vite et TypeScript.

Les imports `@/...` pointent vers `frontend/src`.

Les routes sont centralisées dans `frontend/src/router/router.tsx`. Les routes
protégées passent par `ProtectedRoute`.

## Stack

- React `19` ;
- Vite ;
- TypeScript ;
- Tailwind CSS v4 ;
- shadcn/radix-ui ;
- Zustand ;
- Axios ;
- React Router ;
- Recharts.

## Organisation

```text
frontend/src/api/         clients HTTP par domaine
frontend/src/components/  composants partagés
frontend/src/hooks/       hooks réutilisables
frontend/src/layouts/     layouts application/auth
frontend/src/lib/         helpers métier et formatage
frontend/src/pages/       pages par domaine
frontend/src/router/      routes et protection
frontend/src/stores/      stores Zustand
frontend/src/types/       types TypeScript
```

## Client API

`frontend/src/api/client.ts` centralise la configuration Axios.

Il ajoute automatiquement le token Bearer et redirige vers `/login` en cas de
réponse `401`.

Les fichiers dans `frontend/src/api/` exposent des fonctions par domaine :
véhicules, interventions, documents, profil, dashboard, stock, configuration,
etc.

## Routing

Les routes publiques sont :

- `/login` ;
- `/reset-password/request` ;
- `/reset-password/reset/:token`.

Les autres routes passent par `ProtectedRoute` et utilisent `AppLayout`.

La racine `/` redirige vers `/dashboard`.

## UI et styles

La configuration shadcn est dans `frontend/components.json`.

Tailwind v4 est configuré via le plugin Vite `@tailwindcss/vite`. Les thèmes et
palettes sont définis dans `frontend/src/index.css` et les helpers associés.

## État client

Les stores Zustand gèrent notamment :

- l'utilisateur connecté et le token ;
- la palette de couleurs.

Le token est conservé côté client pour permettre la persistance de session.

## Commandes

```bash
cd frontend
npm install
npm run dev
npm run lint
npm run build
```

`npm run build` lance TypeScript puis le build Vite.

## Documentation liée

Le lien vers la documentation est configurable avec `VITE_DOCS_URL`.

Il est affiché dans la sidebar de l'application et ouvre la documentation dans
un nouvel onglet.
