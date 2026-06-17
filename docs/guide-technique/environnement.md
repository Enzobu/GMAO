# Environnement

Les fichiers d'environnement réels ne doivent pas être documentés avec leurs
valeurs sensibles.

Les variables importantes concernent notamment :

- la base de données ;
- les ports Docker ;
- l'URL du frontend ;
- l'URL de l'API ;
- les clés JWT ;
- le répertoire des documents.

## Fichiers utilisés

Le Makefile sélectionne l'environnement avec la variable `ENV` :

- par défaut : `.env.local` ;
- production : `.env.prod` avec `ENV=prod`.

Exemples :

```bash
make up
make up ENV=prod
```

Le frontend possède aussi son propre fichier `frontend/.env`.

## Variables API principales

Catégories à renseigner côté API :

- `APP_ENV` ;
- `APP_SECRET` ;
- `DATABASE_URL` ;
- variables MariaDB ;
- `TRUSTED_PROXIES` ;
- configuration mailer ;
- clés JWT ;
- `CORS_ALLOW_ORIGIN` ;
- `FRONTEND_URL` ;
- `DOCUMENT_DIRECTORY`.

Ne documentez jamais les valeurs réelles des clés JWT, mots de passe, secrets ou
DSN.

## Variables frontend

Le frontend lit notamment :

- `VITE_API_URL` : URL de l'API ;
- `VITE_DOCS_URL` : URL de la documentation.

Ces variables sont injectées au build Vite. En production Docker, elles sont
passées au build du service `front`.

## Documents

Les documents uploadés dépendent de `DOCUMENT_DIRECTORY` et du paramètre Symfony
`documents_directory`.

En production, le stockage est monté dans un volume persistant. Vérifiez ce
point avant tout redéploiement ou migration de serveur.

## Production

Le fichier `.env.prod` pilote `docker-compose.prod.yml`.

Il doit contenir les ports, URLs, secrets, configuration mailer, JWT et chemins
de stockage attendus par les services `apache`, `front` et `documentation`.
