# Docker

Le Makefile pilote les commandes Docker principales.

Commandes utiles :

```bash
make build
make up
make exec
make down
```

Le service API est exécuté dans le conteneur `apache`.

## Fichiers Compose

- `docker-compose.yml` : environnement local ;
- `docker-compose.prod.yml` : environnement production.

Le choix se fait via `ENV` dans le Makefile.

## Services locaux

En développement, les services principaux sont :

- `database` : MariaDB ;
- `phpmyadmin` : interface base de données ;
- `apache` : API Symfony.

Ports par défaut visibles dans la configuration locale :

- API Apache : `8000` ;
- phpMyAdmin : `8080`.

## Services production

En production, les services principaux sont :

- `database` ;
- `phpmyadmin` ;
- `apache` ;
- `front` ;
- `documentation`.

Le service `front` construit le frontend avec `VITE_API_URL` et
`VITE_DOCS_URL`.

Le service `documentation` construit le site VitePress depuis `docs/`.

## Volumes persistants

En production, les volumes importants sont :

- données MariaDB ;
- documents uploadés ;
- clés JWT.

Ces volumes ne doivent pas être supprimés pendant un redéploiement standard.

## Commandes utiles

```bash
make build
make up
make logs
make ps
make exec
make down
```

Pour la production :

```bash
make build ENV=prod
make up ENV=prod
make logs ENV=prod
```

## Nettoyage

Les commandes `rm-containers` et `prune` peuvent supprimer des ressources Docker.

Avant de les lancer, vérifiez que les volumes ou images concernés peuvent être
perdus.
