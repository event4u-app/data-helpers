---
title: Development Setup
description: Complete guide for setting up the development environment with Docker and Task
---

Complete guide for setting up the development environment with Docker and Task.

## Prerequisites

Before you start, make sure you have:

- **Docker & Docker Compose** - For running containers
- **Task** - Modern task runner (optional but recommended)
- **Git** - Version control
- **PHP 8.2+** - If running without Docker

## Quick Start

### 1. Clone the Repository

```bash
git clone git@github.com:event4u-app/data-helpers.git
cd data-helpers
```

### 2. Install Task (Optional but Recommended)

**macOS:**
```bash
brew install go-task/tap/go-task
```

**Linux:**
```bash
snap install task --classic
```

**Windows:**
```bash
choco install go-task
```

See [official installation guide](https://taskfile.dev/installation/) for other methods.

### 3. Start Development Environment

```bash
# With Task (recommended)
task dev:setup

# Or manually
docker-compose up -d --build
docker exec -it data-helpers-php84 composer install
```

### 4. Verify Installation

```bash
# Run tests
task test:run

# Or manually
docker exec -it data-helpers-php84 vendor/bin/pest
```

## Docker Setup

### Available Containers

The project provides three PHP containers:

- **data-helpers-php82** - PHP 8.2 with Composer & Task
- **data-helpers-php83** - PHP 8.3 with Composer & Task
- **data-helpers-php84** - PHP 8.4 with Composer & Task (default)

### Docker Commands

```bash
# Start containers
task docker:up

# Stop containers
task docker:down

# Restart containers
task docker:restart

# View logs
task docker:logs

# Clean up (remove containers & volumes)
task docker:clean

# Rebuild everything
task docker:rebuild
```

### Shell Access

```bash
# Open shell in PHP 8.4 (default)
task shell

# Open shell in specific PHP version
task shell PHP=8.2
task shell:82
task shell:83
task shell:84
```

## Task Runner

Task is a modern alternative to Make with YAML syntax and better readability.

### Why Task?

- ✅ **Cross-platform** - Works on macOS, Linux, and Windows
- ✅ **Fast** - Written in Go, much faster than Make
- ✅ **Simple** - YAML-based configuration
- ✅ **Powerful** - Variables, dependencies, includes
- ✅ **Beautiful Output** - Consistent formatting with colors and icons

### Task Structure

```
Taskfile.yml              # Main file with aliases
taskfiles/
├── docker.yml            # Docker container management
├── tests.yml             # All testing operations
├── quality.yml           # Code quality checks
├── dev.yml               # Development workflows
├── bench.yml             # Performance benchmarking
└── docs.yml              # Documentation (Starlight)
```

### Available Task Categories

#### 🐳 Docker Management

```bash
task docker:build         # Build containers
task docker:up            # Start containers
task docker:down          # Stop containers
task docker:restart       # Restart containers
task docker:logs          # Show logs
task docker:logs:follow   # Follow logs
task docker:clean         # Remove containers & volumes
task docker:rebuild       # Rebuild everything
```

#### 📦 Dependencies

```bash
task install              # Install dependencies (all containers)
task install:82           # Install in PHP 8.2
task install:83           # Install in PHP 8.3
task install:84           # Install in PHP 8.4
task update               # Update dependencies (PHP 8.4)
task update PHP=8.2       # Update in PHP 8.2
```

#### 🧪 Testing

```bash
# Basic tests
task test:run             # Run tests (PHP 8.4)
task test:run PHP=8.2     # Run tests (PHP 8.2)
task test:unit            # Unit tests only
task test:e2e             # E2E tests only
task test:coverage        # Tests with coverage

# Framework-specific tests (isolated containers)
task test:laravel10       # Laravel 10
task test:laravel11       # Laravel 11
task test:symfony6        # Symfony 6
task test:symfony7        # Symfony 7

# Test matrix (all combinations)
task test:matrix          # Complete test matrix
task test:matrix:plain    # Plain PHP only
task test:matrix:laravel  # All Laravel versions
task test:matrix:symfony  # All Symfony versions
task test:matrix:doctrine # All Doctrine versions
```

#### ✨ Code Quality

```bash
# PHPStan (Level 9)
task quality:phpstan      # Run PHPStan
task quality:phpstan:baseline  # Generate baseline

# ECS (PHP Easy Coding Standard)
task quality:ecs          # Check code style
task quality:ecs:fix      # Fix code style

# Rector (PHP Refactoring)
task quality:rector       # Check refactoring
task quality:rector:fix   # Apply refactoring

# All checks
task quality:check        # Run all quality checks
task dev:pre-commit       # Pre-commit checks
```

#### ⚡ Benchmarking

```bash
task bench:run            # Run benchmarks
task bench:compare        # Compare with baseline
task bench:profile        # Profile performance
```

#### 📚 Documentation

```bash
task docs:dev             # Start documentation server (http://localhost:4321)
task docs:build           # Build documentation
task docs:preview         # Preview production build
```

#### 💻 Development Workflows

```bash
task dev:setup            # Complete development setup
task dev:pre-commit       # Pre-commit checks (ECS + PHPStan + Tests)
task dev:clean            # Clean cache and temp files
task dev:reset            # Reset environment (clean + rebuild)
```

## Common Workflows

### Starting a New Feature

```bash
# 1. Update dependencies
task update

# 2. Create feature branch
git checkout -b feature/my-feature

# 3. Make changes and test
task test:run

# 4. Run quality checks
task quality:check

# 5. Commit and push
git add .
git commit -m "feat: add my feature"
git push origin feature/my-feature
```

### Running Tests Before Commit

```bash
# Quick pre-commit check
task dev:pre-commit

# Or run individual checks
task quality:ecs:fix      # Fix code style
task quality:phpstan      # Static analysis
task test:run             # Run tests
```

### Testing with Multiple PHP Versions

```bash
# Test with PHP 8.2
task test:run PHP=8.2

# Test with PHP 8.3
task test:run PHP=8.3

# Test with PHP 8.4
task test:run PHP=8.4

# Or run complete test matrix
task test:matrix
```

## Troubleshooting

### Docker Issues

**Containers won't start:**
```bash
task docker:clean
task docker:rebuild
```

**Permission issues:**
```bash
# On Linux, you might need to fix permissions
sudo chown -R $USER:$USER .
```

### Task Issues

**Task not found:**
```bash
# Make sure Task is installed
task --version

# Or use direct commands
docker-compose up -d
docker exec -it data-helpers-php84 composer install
```

### Test Failures

**Dependencies out of date:**
```bash
task install
```

**Cache issues:**
```bash
task dev:clean
```

## Next Steps

- [Contributing Guide](/guides/contributing/) - Learn how to contribute
- [Testing Guide](/testing/testing-dtos/) - Learn about testing
- [Troubleshooting](/troubleshooting/common-issues/) - Common issues and solutions

