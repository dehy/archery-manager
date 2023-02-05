NAME = dehy/archery-manager:local
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
	$(BASE_EXEC) composer install
	$(BASE_EXEC) yarn install
	$(BASE_EXEC) yarn run encore dev

migratedb: start
	$(BASE_EXEC) php bin/console doctrine:migrations:migrate

quality:
	php -d "memory_limit=512M" vendor/bin/rector process; \
	PHP_CS_FIXER_IGNORE_ENV=True php -d "memory_limit=512M" vendor/bin/php-cs-fixer fix --allow-risky=yes; \
	php -d "memory_limit=512M" vendor/bin/phpstan analyze