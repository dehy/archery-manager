.PHONY: help install test clean lint
.DEFAULT_GOAL := help

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-15s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## Install dependencies and Playwright browsers
	npm ci
	npx playwright install --with-deps

test: ## Run Playwright tests
	npx playwright test

test-ui: ## Run Playwright tests with UI mode
	npx playwright test --ui

test-headed: ## Run Playwright tests in headed mode
	npx playwright test --headed

report: ## Show Playwright HTML report
	npx playwright show-report

clean: ## Clean test results and reports
	rm -rf test-results/ playwright-report/ blob-report/ node_modules/
