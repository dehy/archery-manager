.PHONY: help install build dev test clean lint fix type-check
.DEFAULT_GOAL := help

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies
	pnpm install --frozen-lockfile

build: ## Build the application
	pnpm run build

dev: ## Start development server
	pnpm run dev

test: ## Run Jest tests
	pnpm run test

test-watch: ## Run Jest tests in watch mode
	pnpm run test:watch

lint: ## Run ESLint
	pnpm run lint

fix: ## Fix ESLint issues
	pnpm run lint --fix

type-check: ## Run TypeScript type checking
	pnpm run type-check

clean: ## Clean build artifacts and dependencies
	rm -rf .next/ node_modules/ coverage/ .swc/
	rm -f tsconfig.tsbuildinfo
