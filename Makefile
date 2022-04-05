NAME = dehy/crowd-news:local
DOCKER_COMPOSE = docker compose
ifeq ($(CI), true)
DOCKER_COMPOSE = docker compose --ansi never
endif
ROOT_EXEC = $(DOCKER_COMPOSE) exec -w /app app
BASE_EXEC = $(DOCKER_COMPOSE) exec -u symfony -w /app app

container:
	$(DOCKER_COMPOSE) build

start:
	$(DOCKER_COMPOSE) up -d

shell: start
	$(BASE_EXEC) bash

shell-root: start
	$(ROOT_EXEC) bash

deps: start
	composer install --no-scripts --ignore-platform-reqs
	yarn install
	$(BASE_EXEC) composer install
	$(BASE_EXEC) yarn install
	$(BASE_EXEC) yarn run encore dev

migratedb: start
	$(BASE_EXEC) php bin/console doctrine:migrations:migrate