.PHONY: help build up down restart logs shell test test-all clean install

# Default PHP version
PHP ?= 8.4

# Colors for output
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m

help: ## Show this help message
	@echo "$(BLUE)Docker Test Environment for event4u/data-helpers$(NC)"
	@echo ""
	@echo "$(YELLOW)Available targets:$(NC)"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Examples:$(NC)"
	@echo "  make build              # Build all containers"
	@echo "  make up                 # Start all containers"
	@echo "  make shell PHP=8.2      # Open shell in PHP 8.2 container"
	@echo "  make test PHP=8.3 FW=l V=11  # Test PHP 8.3 with Laravel 11"
	@echo "  make test-all           # Run all tests from matrix"
	@echo ""

build: ## Build all Docker containers
	@echo "$(BLUE)→$(NC) Building Docker containers..."
	@docker-compose build

up: ## Start all Docker containers
	@echo "$(BLUE)→$(NC) Starting Docker containers..."
	@docker-compose up -d
	@sleep 2
	@echo "$(GREEN)✅  $(NC)Containers started"

down: ## Stop all Docker containers
	@echo "$(BLUE)→$(NC) Stopping Docker containers..."
	@docker-compose down
	@echo "$(GREEN)✅  $(NC)Containers stopped"

restart: down up ## Restart all Docker containers

logs: ## Show logs from all containers
	@docker-compose logs -f

shell: up ## Open shell in container (use PHP=8.2|8.3|8.4)
	@./docker/test.sh -p $(PHP) --shell

install: up ## Install dependencies in all containers
	@echo "$(BLUE)→$(NC) Installing dependencies in all containers..."
	@./docker/test.sh -p 8.2 -i
	@./docker/test.sh -p 8.3 -i
	@./docker/test.sh -p 8.4 -i
	@echo "$(GREEN)✅  $(NC)Dependencies installed"

test: up ## Run tests (use PHP=8.2|8.3|8.4, FW=l|s|d, V=version)
	@if [ -z "$(FW)" ] || [ -z "$(V)" ]; then \
		echo "$(YELLOW)Running standard tests with PHP $(PHP)...$(NC)"; \
		./docker/test.sh -p $(PHP); \
	else \
		echo "$(YELLOW)Running tests with PHP $(PHP), framework $(FW), version $(V)...$(NC)"; \
		./docker/test.sh -p $(PHP) -$(FW) $(V); \
	fi

test-phpstan: up ## Run tests with PHPStan (use PHP=8.2|8.3|8.4, FW=l|s|d, V=version)
	@if [ -z "$(FW)" ] || [ -z "$(V)" ]; then \
		echo "$(YELLOW)Running tests with PHPStan using PHP $(PHP)...$(NC)"; \
		./docker/test.sh -p $(PHP) --phpstan; \
	else \
		echo "$(YELLOW)Running tests with PHPStan using PHP $(PHP), framework $(FW), version $(V)...$(NC)"; \
		./docker/test.sh -p $(PHP) -$(FW) $(V) --phpstan; \
	fi

test-all: up ## Run all tests from test matrix
	@./docker/test-all.sh

test-all-phpstan: up ## Run all tests from test matrix with PHPStan
	@./docker/test-all.sh --phpstan

test-php82: up ## Run all PHP 8.2 tests
	@./docker/test-all.sh -p 8.2

test-php83: up ## Run all PHP 8.3 tests
	@./docker/test-all.sh -p 8.3

test-php84: up ## Run all PHP 8.4 tests
	@./docker/test-all.sh -p 8.4

test-laravel: up ## Run all Laravel tests
	@./docker/test-all.sh -f laravel

test-symfony: up ## Run all Symfony tests
	@./docker/test-all.sh -f symfony

test-doctrine: up ## Run all Doctrine tests
	@./docker/test-all.sh -f doctrine

clean: down ## Remove all containers and volumes
	@echo "$(BLUE)→$(NC) Removing containers and volumes..."
	@docker-compose down -v
	@echo "$(GREEN)✅  $(NC)Cleanup complete"

rebuild: clean build up ## Rebuild everything from scratch

# Quick test shortcuts
l9: ## Test Laravel 9 with PHP 8.2
	@make test PHP=8.2 FW=l V=9

l10: ## Test Laravel 10 with PHP 8.3
	@make test PHP=8.3 FW=l V=10

l11: ## Test Laravel 11 with PHP 8.4
	@make test PHP=8.4 FW=l V=11

s6: ## Test Symfony 6 with PHP 8.3
	@make test PHP=8.3 FW=s V=6

s7: ## Test Symfony 7 with PHP 8.4
	@make test PHP=8.4 FW=s V=7

d2: ## Test Doctrine 2 with PHP 8.2
	@make test PHP=8.2 FW=d V=2

d3: ## Test Doctrine 3 with PHP 8.4
	@make test PHP=8.4 FW=d V=3

