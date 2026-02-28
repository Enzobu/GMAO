build:
	docker compose --env-file .env.local --progress=plain build

up:
	docker compose --env-file .env.local up -d

logs:
	docker compose --env-file .env.local logs -f

down:
	docker compose --env-file .env.local down

restart: down up

exec:
	docker compose --env-file .env.local exec apache /bin/bash

init-symfony:
	docker compose --env-file .env.local exec apache sh -c "./init-symfony.sh"

init: build up init-symfony

# Command to remove Symfony project files only
rm-symfony:
	docker compose --env-file .env.local exec apache sh -c \
	"rm -rf \
	./assets \
	./bin \
	./config \
	./migrations \
	./public/index.php \
	./src \
	./templates \
	./tests \
	./translations \
	./var \
	./vendor \
	./composer.json \
	./composer.lock \
	./symfony.lock \
	./phpunit.xml.dist \
	./.env \
	./.env.test \
	./.gitignore \
	./importmap.php"

# Command to remove Docker containers and volumes
rm-containers:
	docker compose --env-file .env.local down -v

# Command to prune unused Docker data
prune:
	docker system prune -f --volumes
