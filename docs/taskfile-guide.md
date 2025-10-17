# Taskfile - Task Runner Guide

This project uses [Task](https://taskfile.dev) as a modern task runner. Task is an alternative to Make with YAML syntax and better readability.

## Installation

### macOS
```bash
brew install go-task/tap/go-task
```

### Linux
```bash
sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b ~/.local/bin
```

### Windows
```bash
choco install go-task
```

Or see [official installation guide](https://taskfile.dev/installation/).

## Quick Start

```bash
# Show all available tasks
task --list

# Setup development environment
task dev:setup

# Run tests
task test

# Run tests with specific PHP version
task test PHP=8.2

# Quick shortcuts
task l11              # Laravel 11 with PHP 8.4
task s7               # Symfony 7 with PHP 8.4
```

## Task Categories

### 🐳 Docker Management

```bash
task docker:build     # Build containers
task docker:up        # Start containers
task docker:down      # Stop containers
task docker:restart   # Restart containers
task docker:logs      # Show logs
task docker:clean     # Remove containers & volumes
task docker:rebuild   # Rebuild everything
```

### 🖥️ Shell Access

```bash
task shell            # Shell in PHP 8.4 (default)
task shell PHP=8.2    # Shell in PHP 8.2
task shell:82         # Shell in PHP 8.2
task shell:83         # Shell in PHP 8.3
task shell:84         # Shell in PHP 8.4
```

### 📦 Dependencies

```bash
task install          # Install dependencies in all containers
task install:82       # Install dependencies in PHP 8.2
task install:83       # Install dependencies in PHP 8.3
task install:84       # Install dependencies in PHP 8.4
task update           # Update dependencies (default PHP 8.4)
task update PHP=8.2   # Update dependencies in PHP 8.2
```

### 🧪 Tests - Basic

```bash
task test             # Tests with PHP 8.4 (default)
task test PHP=8.2     # Tests with PHP 8.2
task test:82          # Tests with PHP 8.2
task test:83          # Tests with PHP 8.3
task test:84          # Tests with PHP 8.4
task test:coverage    # Tests with coverage
task test:full        # Full test suite incl. E2E
```

### 🧪 Tests - Laravel

```bash
task test:laravel9    # Laravel 9 with PHP 8.2
task test:laravel10   # Laravel 10 (default PHP 8.3)
task test:laravel11   # Laravel 11 (default PHP 8.4)
task test:all-laravel # All Laravel versions

# With specific PHP version
task test:laravel10 PHP=8.2
task test:laravel11 PHP=8.3
```

### 🧪 Tests - Symfony

```bash
task test:symfony6    # Symfony 6
task test:symfony7    # Symfony 7
task test:all-symfony # All Symfony versions

# With specific PHP version
task test:symfony6 PHP=8.2
task test:symfony7 PHP=8.3
```

### 🧪 Tests - Doctrine

```bash
task test:doctrine2   # Doctrine 2
task test:doctrine3   # Doctrine 3
task test:all-doctrine # All Doctrine versions

# With specific PHP version
task test:doctrine2 PHP=8.3
task test:doctrine3 PHP=8.4
```

### 🧪 Tests - Matrix & E2E

```bash
task test:all         # All framework tests
task test:matrix      # Full test matrix
task test:matrix:82   # Matrix for PHP 8.2 only
task test:matrix:83   # Matrix for PHP 8.3 only
task test:matrix:84   # Matrix for PHP 8.4 only

task test:e2e         # E2E tests
task test:e2e-laravel # Laravel E2E tests
task test:e2e-symfony # Symfony E2E tests
```

### 🔍 Code Quality - PHPStan

```bash
task phpstan          # PHPStan with PHP 8.4 (default)
task phpstan PHP=8.2  # PHPStan with PHP 8.2
task phpstan:82       # PHPStan with PHP 8.2
task phpstan:83       # PHPStan with PHP 8.3
task phpstan:84       # PHPStan with PHP 8.4
task phpstan:baseline # Generate baseline
task phpstan:clear    # Clear cache
```

### 🔍 Code Quality - ECS & Rector

```bash
task ecs              # Code style check
task ecs:fix          # Code style fix
task rector           # Rector dry-run
task rector:fix       # Rector apply
task refactor         # ECS + Rector dry-run
task refactor:fix     # ECS + Rector apply
task quality          # All quality checks
task quality:fix      # All quality checks with fixes
```

### 📊 Benchmarks

```bash
task benchmark        # Run benchmarks
task benchmark:readme # Update benchmark results in README
```

### 🗑️ Cache

```bash
task cache:clear      # Clear cache
task cache:stats      # Show cache statistics
```

### ⚡ Quick Shortcuts

```bash
task l9               # Laravel 9 with PHP 8.2
task l10              # Laravel 10 with PHP 8.3
task l11              # Laravel 11 with PHP 8.4
task s6               # Symfony 6 with PHP 8.3
task s7               # Symfony 7 with PHP 8.4
task d2               # Doctrine 2 with PHP 8.2
task d3               # Doctrine 3 with PHP 8.4
```

### 🚀 CI Simulation

```bash
task ci               # Simulate full CI pipeline
task ci:82            # CI for PHP 8.2
task ci:83            # CI for PHP 8.3
task ci:84            # CI for PHP 8.4
```

### 💻 Development Workflows

```bash
task dev:setup        # Complete setup
task dev:reset        # Reset environment
task dev:test         # Quick test (PHP 8.4)
task dev:quality      # Quick quality check (PHP 8.4)
```

### 🔒 Pre-commit / Pre-push

```bash
task pre-commit       # Checks before commit (fix + test)
task pre-push         # Checks before push (quality + full tests)
```

### ℹ️ Info

```bash
task info             # Show environment information
task --list           # List all tasks
```

## Variables

You can control PHP versions via the `PHP` variable:

```bash
# Default (PHP 8.4)
task test

# With PHP 8.2
task test PHP=8.2

# With PHP 8.3
task test PHP=8.3
```

Available values:
- `PHP=8.2` → Container `data-helpers-php82`
- `PHP=8.3` → Container `data-helpers-php83`
- `PHP=8.4` → Container `data-helpers-php84`

## Typical Workflows

### Developing a new feature

```bash
# Setup
task dev:setup

# Develop & test
task dev:test

# Check code quality
task dev:quality

# Before commit
task pre-commit

# Before push
task pre-push
```

### Run all tests locally

```bash
# Start containers
task docker:up

# Install dependencies
task install

# Full test matrix
task test:matrix

# With PHPStan
task ci
```

### Debugging

```bash
# Open shell
task shell:84

# In container
composer install
./scripts/test-with-versions.sh -l 11 -p
vendor/bin/pest --filter=MyTest
exit
```

### Clean up code

```bash
# Apply fixes
task refactor:fix

# Test
task test

# Quality check
task quality
```

## Advantages over Make

✅ **YAML syntax** - More readable than Makefile  
✅ **Dependencies** - Automatic task dependencies  
✅ **Variables** - Easy variable passing  
✅ **Cross-platform** - Works the same on all platforms  
✅ **Descriptions** - Every task has a description  
✅ **Auto-complete** - Shell completion available  

## Tips

1. **Use `task --list`** to see all available tasks
2. **Use quick shortcuts** like `task l11` instead of long commands
3. **Use `task info`** to check container status
4. **Use `task pre-push`** before every push
5. **Use `task ci`** to simulate CI pipeline locally

## Further Information

- [Task Documentation](https://taskfile.dev)
- [Docker Setup](docker-setup.md)

