.PHONY: help install build test clean lint fix up down logs shell-api shell-pwa
.DEFAULT_GOAL := help

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Docker Compose targets
up: ## Start all services with Docker Compose
	docker compose up -d

down: ## Stop all services
	docker compose down

logs: ## Show logs from all services
	docker compose logs -f

restart: ## Restart all services
	docker compose restart

# Installation targets
install: ## Install dependencies in all projects
	@echo "📦 Installing API dependencies..."
	$(MAKE) -C api install
	@echo "📦 Installing PWA dependencies..."
	$(MAKE) -C pwa install
	@echo "📦 Installing E2E dependencies..."
	$(MAKE) -C e2e install
	@echo "✅ All dependencies installed!"

install-api: ## Install only API dependencies
	$(MAKE) -C api install

install-pwa: ## Install only PWA dependencies
	$(MAKE) -C pwa install

install-e2e: ## Install only E2E dependencies
	$(MAKE) -C e2e install

fresh-install: clean install ## Clean everything and reinstall all dependencies
	@echo "🎉 Fresh installation complete!"

# Build targets
build: ## Build all projects
	@echo "🔨 Building PWA..."
	$(MAKE) -C pwa build

# Test targets
test: ## Run tests in all projects
	@echo "🧪 Running API tests..."
	$(MAKE) -C api test
	@echo "🧪 Running PWA tests..."
	$(MAKE) -C pwa test

test-e2e: up ## Run E2E tests (requires services to be up)
	@echo "🎭 Running E2E tests..."
	$(MAKE) -C e2e test

# Linting and fixing
lint: ## Run linting in all projects
	@echo "🔍 Linting API..."
	$(MAKE) -C api lint
	@echo "🔍 Linting PWA..."
	$(MAKE) -C pwa lint

fix: ## Fix code style in all projects
	@echo "🎨 Fixing API code style..."
	$(MAKE) -C api fix
	@echo "🎨 Fixing PWA code style..."
	$(MAKE) -C pwa fix

# Type checking
type-check: ## Run TypeScript type checking in PWA
	@echo "📝 Type checking PWA..."
	$(MAKE) -C pwa type-check

# Clean targets
clean: ## Clean all projects (cache, build artifacts, reports, dependencies)
	@echo "🧹 Cleaning API..."
	$(MAKE) -C api clean
	@echo "🧹 Cleaning PWA..."
	$(MAKE) -C pwa clean
	@echo "🧹 Cleaning E2E..."
	$(MAKE) -C e2e clean
	@echo "🧹 Cleaning Docker..."
	docker compose down --volumes --remove-orphans

# Development helpers
shell-api: up ## Open a shell in the API container
	docker compose exec php bash

shell-pwa: up ## Open a shell in the PWA container
	docker compose exec pwa bash

# Database targets
db-migrate: up ## Run database migrations
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction

db-reset: up ## Reset database (drop, create, migrate, fixtures)
	docker compose exec php bin/console doctrine:database:drop --force --if-exists
	docker compose exec php bin/console doctrine:database:create
	docker compose exec php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec php bin/console doctrine:fixtures:load --no-interaction

# Production targets
deploy-build: ## Build Docker images for production
	docker compose -f compose.yaml -f compose.prod.yaml build

# Quality targets
quality: lint type-check test ## Run all quality checks (lint, type-check, test)

# CI simulation
ci: install quality test-e2e ## Simulate CI pipeline locally
