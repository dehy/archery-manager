.PHONY: help install test clean lint fix
.DEFAULT_GOAL := help

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install PHP dependencies
	composer install --prefer-dist --no-progress --optimize-autoloader

test: ## Run PHPUnit tests
	vendor/bin/simple-phpunit

test-coverage: ## Run PHPUnit tests with coverage
	vendor/bin/simple-phpunit --coverage-html var/coverage

lint: ## Run PHPStan static analysis
	vendor/bin/phpstan analyse src tests --level=2

fix: ## Fix coding standards with PHP CS Fixer
	vendor/bin/php-cs-fixer fix

check: ## Check coding standards without fixing
	vendor/bin/php-cs-fixer fix --dry-run --diff

clean: ## Clean cache and temporary files
	rm -rf var/cache/* var/log/* var/coverage/* vendor/
	rm -f .php-cs-fixer.cache .phpunit.result.cache
	rm -f coverage.xml phpunit-report.xml phpstan-report.xml php-cs-fixer-report.xml
