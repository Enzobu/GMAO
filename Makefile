# =====================
# MAKEFILE - Docker Compose + Symfony
# =====================

SHELL := /bin/bash

.PHONY: help build up down stop restart logs ps exec \
        init-symfony init mep \
        pull rebuild recreate prune \
        rm-symfony rm-containers prune

# =====================
# CONFIG
# =====================

ENV ?= dev

COMPOSE_DEV  = docker-compose.yml
COMPOSE_PROD = docker-compose.prod.yml

ENV_FILE_BASE = .env.local
ENV_FILE_PROD = .env.prod

ifeq ($(ENV),prod)
COMPOSE_FILE = $(COMPOSE_PROD)
ENV_FILES    =  --env-file $(ENV_FILE_PROD)
else
COMPOSE_FILE = $(COMPOSE_DEV)
ENV_FILES    = --env-file $(ENV_FILE_BASE)
endif

DOCKER_COMPOSE = docker compose -f $(COMPOSE_FILE) $(ENV_FILES)

# =====================
# HELP
# =====================

help:
	@echo "Docker / Compose:"
	@echo "  build              Build images ($(ENV))"
	@echo "  up                 Start containers ($(ENV))"
	@echo "  down               Stop + remove containers ($(ENV))"
	@echo "  stop               Stop containers ($(ENV))"
	@echo "  restart            down + up ($(ENV))"
	@echo "  logs               Follow logs ($(ENV))"
	@echo "  ps                 Show containers ($(ENV))"
	@echo "  exec               Bash into apache container ($(ENV))"
	@echo ""
	@echo "Symfony:"
	@echo "  init-symfony       Run ./init-symfony.sh ($(ENV))"
	@echo "  init               build + up + init-symfony ($(ENV))"
	@echo "  mep                Reset DB + fixtures (DEV ONLY)"
	@echo ""
	@echo "Cleanup:"
	@echo "  rm-symfony         Remove Symfony files inside container ($(ENV))"
	@echo "  rm-containers      down -v ($(ENV))"
	@echo "  prune              docker system prune -f --volumes"
	@echo ""
	@echo "Examples:"
	@echo "  make up"
	@echo "  make up ENV=prod"
	@echo "  make logs ENV=prod"

# =====================
# DOCKER
# =====================

build:
	$(DOCKER_COMPOSE) --progress=plain build

up:
	$(DOCKER_COMPOSE) up -d

down:
	$(DOCKER_COMPOSE) down

stop:
	$(DOCKER_COMPOSE) stop

restart: down up

logs:
	$(DOCKER_COMPOSE) logs -f

ps:
	$(DOCKER_COMPOSE) ps

exec:
	$(DOCKER_COMPOSE) exec apache /bin/bash

# =====================
# SYMFONY
# =====================

init-symfony:
	$(DOCKER_COMPOSE) exec apache sh -c "./init-symfony.sh"

init: build up init-symfony

mep:
ifeq ($(ENV),prod)
	@$(MAKE) pull rebuild recreate prune ENV=prod
else
	$(DOCKER_COMPOSE) exec apache bash -c "\
	composer install && \
	symfony console sass:build && \
	symfony console doctrine:database:drop --force && \
	symfony console doctrine:database:create && \
	symfony console doctrine:schema:update --force -n && \
	symfony console doctrine:fixtures:load -n && \
	symfony console cache:clear"
endif

pull:
	@git pull

rebuild:
	$(DOCKER_COMPOSE) build --no-cache

recreate:
	$(DOCKER_COMPOSE) up -d --force-recreate

prune:
	docker image prune -f


# =====================
# CLEANUP
# =====================

rm-symfony:
	$(DOCKER_COMPOSE) exec apache sh -c "\
	rm -rf \
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

rm-containers:
	$(DOCKER_COMPOSE) down -v