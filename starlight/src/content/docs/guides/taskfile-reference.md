---
title: Taskfile Reference
description: Complete reference of all available Task commands
---

Complete reference of all available Task commands for Data Helpers development.

## Introduction

This project uses [Task](https://taskfile.dev/) as the task runner. Task is a modern alternative to Make with YAML syntax and better readability.

### Why Task?

- ✅ **Cross-platform** - Works on macOS, Linux, and Windows
- ✅ **Fast** - Written in Go, much faster than Make
- ✅ **Simple** - YAML-based configuration
- ✅ **Powerful** - Variables, dependencies, includes
- ✅ **Beautiful Output** - Consistent formatting with colors and icons

### Installation

See [Development Setup](/guides/development-setup/#install-task-optional-but-recommended) for installation instructions.

## Quick Reference

```bash
# Show all available tasks
task

# Show detailed task list
task --list

# Start Docker containers
task docker:up

# Run tests
task test:run

# Run all quality checks
task quality:check

# Complete development setup
task dev:setup
```

## Task Categories

### 🐳 Docker Management

Manage Docker containers and environment.

```bash
task docker:build         # Build containers
task docker:up            # Start containers
task docker:down          # Stop containers
task docker:restart       # Restart containers
task docker:logs          # Show logs
task docker:logs:follow   # Follow logs (tail -f)
task docker:clean         # Remove containers & volumes
task docker:rebuild       # Clean + build + up
task docker:ps            # Show container status
```

**Variables:**
- `PHP=8.2|8.3|8.4` - Specify PHP version (default: 8.4)

**Examples:**
```bash
# Start containers
task docker:up

# View logs
task docker:logs

# Rebuild everything
task docker:rebuild
```

### 🖥️ Shell Access

Open shell in Docker containers.

```bash
task shell                # Shell in PHP 8.4 (default)
task shell PHP=8.2        # Shell in PHP 8.2
task shell:82             # Shell in PHP 8.2
task shell:83             # Shell in PHP 8.3
task shell:84             # Shell in PHP 8.4
```

**Examples:**
```bash
# Open shell in PHP 8.4
task shell

# Open shell in PHP 8.2
task shell:82
```

### 📦 Dependencies

Install and update dependencies.

```bash
task install              # Install dependencies (all containers)
task install:82           # Install in PHP 8.2
task install:83           # Install in PHP 8.3
task install:84           # Install in PHP 8.4
task update               # Update dependencies (PHP 8.4)
task update PHP=8.2       # Update in PHP 8.2
```

**Examples:**
```bash
# Install dependencies in all containers
task install

# Update dependencies in PHP 8.4
task update
```

### 🧪 Testing - Basic

Run unit and E2E tests.

```bash
task test:run             # Run tests (PHP 8.4)
task test:run PHP=8.2     # Run tests (PHP 8.2)
task test:unit            # Unit tests only
task test:e2e             # E2E tests only
task test:coverage        # Tests with coverage
task test:full            # Full test suite (unit + e2e)
task test:documentation   # Documentation tests
```

**Aliases:**
- `task test` → `task test:run`
- `task test:docs` → `task test:documentation`

**Examples:**
```bash
# Run tests with PHP 8.4
task test:run

# Run tests with PHP 8.2
task test:run PHP=8.2

# Run with coverage
task test:coverage
```

### 🧪 Testing - Framework Specific

Test with specific frameworks (isolated containers).

```bash
# Laravel
task test:laravel10       # Laravel 10 (PHP 8.3)
task test:laravel11       # Laravel 11 (PHP 8.4)
task test:l10             # Alias for laravel10
task test:l11             # Alias for laravel11

# Symfony
task test:symfony6        # Symfony 6 (PHP 8.4)
task test:symfony7        # Symfony 7 (PHP 8.4)
task test:s6              # Alias for symfony6
task test:s7              # Alias for symfony7

# Doctrine
task test:doctrine2       # Doctrine 2 (PHP 8.4)
task test:doctrine3       # Doctrine 3 (PHP 8.4)
task test:d2              # Alias for doctrine2
task test:d3              # Alias for doctrine3

# Plain PHP
task test:plain           # Plain PHP (PHP 8.4)
```

**Variables:**
- `PHP=8.2|8.3|8.4` - Specify PHP version

**Examples:**
```bash
# Test Laravel 11 with PHP 8.4
task test:laravel11

# Test Symfony 7 with PHP 8.2
task test:symfony7 PHP=8.2
```

### 🧪 Testing - Test Matrix

Run comprehensive test matrix.

```bash
# Complete matrix
task test:matrix          # All 33 tests

# By PHP version
task test:matrix:82       # All PHP 8.2 tests (11 tests)
task test:matrix:83       # All PHP 8.3 tests (11 tests)
task test:matrix:84       # All PHP 8.4 tests (11 tests)

# By framework
task test:matrix:plain    # Plain PHP only (3 tests)
task test:matrix:laravel  # All Laravel tests (6 tests)
task test:matrix:symfony  # All Symfony tests (6 tests)
task test:matrix:doctrine # All Doctrine tests (6 tests)

# By framework version
task test:matrix:laravel9  # Laravel 9 (3 tests)
task test:matrix:laravel10 # Laravel 10 (2 tests)
task test:matrix:laravel11 # Laravel 11 (3 tests)
task test:matrix:symfony6  # Symfony 6 (3 tests)
task test:matrix:symfony7  # Symfony 7 (3 tests)
task test:matrix:doctrine2 # Doctrine 2 (3 tests)
task test:matrix:doctrine3 # Doctrine 3 (3 tests)
```

**Examples:**
```bash
# Run complete matrix
task test:matrix

# Run all Laravel tests
task test:matrix:laravel

# Run all PHP 8.4 tests
task test:matrix:84
```

### ✨ Code Quality - PHPStan

Static analysis with PHPStan Level 9.

```bash
task quality:phpstan      # Run PHPStan (PHP 8.4)
task quality:phpstan:82   # Run PHPStan (PHP 8.2)
task quality:phpstan:83   # Run PHPStan (PHP 8.3)
task quality:phpstan:84   # Run PHPStan (PHP 8.4)
task quality:phpstan:baseline  # Generate baseline
task quality:phpstan:clear     # Clear cache
```

**Variables:**
- `PHP=8.2|8.3|8.4` - Specify PHP version

**Examples:**
```bash
# Run PHPStan with PHP 8.4
task quality:phpstan

# Run PHPStan with PHP 8.2
task quality:phpstan PHP=8.2

# Generate baseline
task quality:phpstan:baseline
```

### ✨ Code Quality - ECS

Code style checks with PHP Easy Coding Standard.

```bash
task quality:ecs          # Check code style
task quality:ecs:fix      # Fix code style automatically
```

**Variables:**
- `PHP=8.2|8.3|8.4` - Specify PHP version

**Examples:**
```bash
# Check code style
task quality:ecs

# Fix code style
task quality:ecs:fix
```

### ✨ Code Quality - Rector

PHP refactoring with Rector.

```bash
task quality:rector       # Run Rector dry-run
task quality:rector:fix   # Apply Rector changes
```

**Variables:**
- `PHP=8.2|8.3|8.4` - Specify PHP version

**Examples:**
```bash
# Check what Rector would change
task quality:rector

# Apply Rector changes
task quality:rector:fix
```

### ✨ Code Quality - Combined

Run all quality checks at once.

```bash
task quality:check        # Run all quality checks (ECS + PHPStan + Rector)
```

**Examples:**
```bash
# Run all quality checks
task quality:check
```

### ⚡ Benchmarking

Performance benchmarking.

```bash
task bench:run            # Run benchmarks
task bench:compare        # Compare with baseline
task bench:profile        # Profile performance
```

**Examples:**
```bash
# Run benchmarks
task bench:run

# Compare with baseline
task bench:compare
```

### 📚 Documentation

Starlight documentation server.

```bash
task docs:dev             # Start documentation server (http://localhost:4321)
task docs:build           # Build documentation
task docs:preview         # Preview production build
task docs:clean           # Clean documentation cache
```

**Examples:**
```bash
# Start documentation server
task docs:dev

# Build documentation
task docs:build
```

### 💻 Development Workflows

Common development workflows.

```bash
task dev:setup            # Complete development setup
task dev:pre-commit       # Pre-commit checks (ECS + PHPStan + Tests)
task dev:pre-push         # Pre-push checks (Quality + Matrix)
task dev:clean            # Clean cache and temp files
task dev:reset            # Reset environment (clean + rebuild)
```

**Examples:**
```bash
# Setup development environment
task dev:setup

# Run pre-commit checks
task dev:pre-commit

# Reset environment
task dev:reset
```

## Common Workflows

### Starting Development

```bash
# 1. Setup environment
task dev:setup

# 2. Open shell
task shell

# 3. Make changes and test
task test:run
```

### Before Commit

```bash
# Run pre-commit checks
task dev:pre-commit

# Or run individual checks
task quality:ecs:fix      # Fix code style
task quality:phpstan      # Static analysis
task test:run             # Run tests
```

### Before Push

```bash
# Run pre-push checks (includes matrix tests)
task dev:pre-push

# Or run complete test suite
task test:run
```

## Next Steps

- [Development Setup](/guides/development-setup/) - Setup your environment
- [Test Matrix](/guides/test-matrix/) - Learn about the test matrix
- [Contributing Guide](/guides/contributing/) - Learn how to contribute

