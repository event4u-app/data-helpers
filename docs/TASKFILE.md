# Taskfile Documentation

**Version:** 2.0
**Last Updated:** 2025-01-19

---

## Overview

This project uses [Task](https://taskfile.dev/) as the task runner for all development operations. The Taskfile is organized into logical categories with beautiful, consistent output formatting.

### Why Task?

- ✅ **Cross-platform** - Works on macOS, Linux, and Windows
- ✅ **Fast** - Written in Go, much faster than Make
- ✅ **Simple** - YAML-based configuration
- ✅ **Powerful** - Variables, dependencies, includes, and more
- ✅ **Beautiful Output** - Consistent formatting with colors and icons

---

## Installation

### macOS (Homebrew)
```bash
brew install go-task/tap/go-task
```

### Linux (Snap)
```bash
snap install task --classic
```

### Other Methods
See [official installation guide](https://taskfile.dev/installation/)

---

## Quick Start

```bash
# Show all available tasks
task

# Show detailed task list
task --list

# Start Docker containers
task docker:up

# Run tests
task test:run

# Run all quality checks (ECS + Rector + PHPStan + Tests)
task quality:check

# Complete development setup
task dev:setup
```

---

## Task Structure

The Taskfile is organized into separate files for better maintainability:

```
Taskfile.yml              # Main file with aliases and overview
taskfiles/
├── docker.yml            # Docker container management
├── tests.yml             # All testing operations
├── quality.yml           # Code quality checks (PHPStan, ECS, Rector)
├── dev.yml               # Development workflows and utilities
└── bench.yml             # Performance benchmarking
```

---

## Task Categories

### 🐳 Docker Management (`docker:*`)

Manage Docker containers and environment.

```bash
# Build containers
task docker:build

# Start containers
task docker:up

# Stop containers
task docker:down

# Restart containers
task docker:restart

# Show logs
task docker:logs
task docker:logs:follow
task docker:logs:php82

# Clean up
task docker:clean          # Remove containers and volumes
task docker:rebuild        # Clean + build + up

# Status
task docker:ps             # Show container status
task docker:stats          # Show resource usage
task docker:prune          # Remove unused Docker resources
```

### 🧪 Testing (`test:*`)

Run tests across different PHP versions and frameworks with **isolated framework testing**.

#### Complete Test Suite

```bash
# Run EVERYTHING (matrix + e2e)
task test:run              # Complete test suite (recommended for CI)

# Run specific test types
task test:unit             # Unit tests only (default PHP 8.4)
task test:matrix           # Complete test matrix (plain + all frameworks isolated)
task test:e2e              # E2E tests (all frameworks combined)
```

#### Test Matrix (Isolated Framework Tests)

The test matrix runs tests with **isolated frameworks** - only one framework installed at a time.

```bash
# Complete matrix
task test:matrix           # All tests (plain + all frameworks isolated)

# By PHP version
task test:matrix:82        # All PHP 8.2 tests (plain + all frameworks)
task test:matrix:83        # All PHP 8.3 tests (plain + all frameworks)
task test:matrix:84        # All PHP 8.4 tests (plain + all frameworks)

# By framework (all PHP versions)
task test:matrix:plain     # Plain PHP (no frameworks)
task test:matrix:laravel   # All Laravel versions (isolated)
task test:matrix:symfony   # All Symfony versions (isolated)
task test:matrix:doctrine  # All Doctrine versions (isolated)

# By framework version (all PHP versions)
task test:matrix:laravel9  # Laravel 9 on all compatible PHP versions
task test:matrix:laravel10 # Laravel 10 on all compatible PHP versions
task test:matrix:laravel11 # Laravel 11 on all compatible PHP versions
task test:matrix:symfony6  # Symfony 6 on all PHP versions
task test:matrix:symfony7  # Symfony 7 on all PHP versions
task test:matrix:doctrine2 # Doctrine 2 on all PHP versions
task test:matrix:doctrine3 # Doctrine 3 on all PHP versions
```

#### Individual Framework Tests (Quick Access)

```bash
# Laravel (isolated)
task test:laravel9         # Laravel 9 only (PHP 8.2)
task test:laravel10        # Laravel 10 only (PHP 8.3)
task test:laravel11        # Laravel 11 only (PHP 8.4)

# Symfony (isolated)
task test:symfony6         # Symfony 6 only (PHP 8.4)
task test:symfony7         # Symfony 7 only (PHP 8.4)

# Doctrine (isolated)
task test:doctrine2        # Doctrine 2 only (PHP 8.4)
task test:doctrine3        # Doctrine 3 only (PHP 8.4)

# Plain PHP
task test:plain            # No frameworks (PHP 8.4)
```

#### E2E Tests (Combined Frameworks)

```bash
# E2E tests (all frameworks installed together)
task test:e2e              # All e2e tests
task test:e2e:laravel      # Laravel e2e tests
task test:e2e:symfony      # Symfony e2e tests
```

#### Coverage

```bash
task test:coverage         # Run tests with coverage
task test:coverage PHP=8.2 # Coverage with specific PHP version
```

### ✨ Code Quality (`quality:*`)

Run code quality checks and fixes.

```bash
# All-in-One Quality Checks
task quality:check         # Run ALL quality checks (ECS + Rector + PHPStan + Tests)
task quality:fix           # Run all quality fixes (ECS fix + Rector fix + PHPStan + Tests)

# Individual Tools
task quality:phpstan       # Run PHPStan analysis
task quality:ecs           # Run ECS code style check
task quality:rector        # Run Rector dry-run

# With Fixes
task quality:ecs:fix       # Fix code style with ECS
task quality:rector:fix    # Apply Rector changes
task quality:refactor      # Run ECS + Rector dry-run
task quality:refactor:fix  # Apply ECS + Rector fixes

# PHPStan Specific
task quality:phpstan:82    # Run with PHP 8.2
task quality:phpstan:83    # Run with PHP 8.3
task quality:phpstan:84    # Run with PHP 8.4
task quality:phpstan:baseline  # Generate baseline
task quality:phpstan:clear     # Clear cache

```

**💡 Recommended:** Use `task quality:check` to run all checks at once!

### 💻 Development (`dev:*`)

Development workflows and utilities.

```bash
# Shell access
task dev:shell             # Open shell in PHP 8.4 container
task dev:shell PHP=8.2     # Open shell with specific PHP version
task dev:shell:82          # Open shell in PHP 8.2 container
task dev:shell:83          # Open shell in PHP 8.3 container
task dev:shell:84          # Open shell in PHP 8.4 container

# Dependencies
task dev:install           # Install dependencies in all containers
task dev:install:82        # Install in PHP 8.2 container
task dev:install:83        # Install in PHP 8.3 container
task dev:install:84        # Install in PHP 8.4 container
task dev:update            # Update dependencies

# Workflows
task dev:setup             # Complete development setup
task dev:reset             # Reset development environment
task dev:test              # Quick test (PHP 8.4)
task dev:check             # Quick quality check (PHP 8.4)

# Pre-commit/push
task dev:pre-commit        # Run checks before commit
task dev:pre-push          # Run checks before push

# Cache
task dev:cache:clear       # Clear cache
task dev:cache:stats       # Show cache statistics

# Utilities
task dev:sort-deps         # Sort dependencies in composer.json
task dev:info              # Show environment information

# Aliases
task shell                 # Alias for dev:shell
```

### ⚡ Benchmarks (`bench:*`)

Performance benchmarking.

```bash
# Run benchmarks
task bench:run             # Run benchmarks
task bench:readme          # Update benchmark results in README

# DTO benchmarks
task bench:dto             # Run DTO benchmarks
task bench:dto:readme      # Update DTO benchmark results

# All benchmarks
task bench:all             # Run all benchmarks and update docs
```

---

## Common Workflows

### First Time Setup

```bash
# Complete development setup
task dev:setup
```

This will:
1. Build Docker containers
2. Start containers
3. Install dependencies in all PHP versions

### Daily Development

```bash
# Start containers
task docker:up

# Run tests
task test:run

# Run all quality checks (ECS + Rector + PHPStan + Tests)
task quality:check

# Open shell
task dev:shell
```

### Before Committing

```bash
# Run pre-commit checks (fixes code style + runs tests)
task dev:pre-commit
```

### Before Pushing

```bash
# Run pre-push checks (full quality + full tests)
task dev:pre-push
```

### Testing Specific Framework

```bash
# Test Laravel 11
task l11

# Test Symfony 7
task s7

# Test Doctrine 3
task d3
```

### Running Full Test Suite

```bash
# Run all quality checks + full test matrix
task quality:check         # Quality checks (ECS + Rector + PHPStan + Tests)
task test:matrix           # Full test matrix (all PHP versions + frameworks)

# Or run both
task quality:check && task test:matrix
```

---

## Output Formatting

All tasks use a consistent output format with:

- **Headers** - Blue lines with task title
- **Success** - Green ✅ messages
- **Errors** - Red ❌ messages
- **Warnings** - Yellow ⚠️ messages
- **Info** - Cyan ℹ️ messages
- **Steps** - Yellow → indicators

Example output:
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  Running Tests (PHP 8.4)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

[test output]

✅  All tests passed!
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## PHP Version Selection

Most tasks support PHP version selection via the `PHP` variable:

```bash
# Use default PHP 8.4
task test:run

# Use specific PHP version
task test:run PHP=8.2
task test:run PHP=8.3
task test:run PHP=8.4

# Or use version-specific tasks
task test:run:82
task test:run:83
task test:run:84
```

---

## Tips & Tricks

### List All Tasks

```bash
# Show categorized overview
task

# Show detailed list with descriptions
task --list

# Show tasks from specific category
task --list | grep "docker:"
```

### Dry Run

```bash
# See what a task would do without executing
task --dry test:run
```

### Parallel Execution

```bash
# Run multiple tasks in parallel
task --parallel test:run:82 test:run:83 test:run:84
```

### Watch Mode

```bash
# Re-run task on file changes
task --watch test:run
```

---

## Troubleshooting

### Task Not Found

```bash
# Make sure Task is installed
task --version

# If not installed, see Installation section above
```

### Docker Containers Not Running

```bash
# Start containers
task docker:up

# Or rebuild everything
task docker:rebuild
```

### Permission Errors

```bash
# Make sure scripts are executable
chmod +x scripts/*.sh
```

### Slow Performance

```bash
# Clean up Docker resources
task docker:prune

# Reset environment
task dev:reset
```

---

## Migration from Makefile

The old `Makefile` has been replaced with this Taskfile. Here's a quick migration guide:

| Old (Make) | New (Task) |
|------------|------------|
| `make build` | `task docker:build` |
| `make up` | `task docker:up` or `task up` |
| `make down` | `task docker:down` or `task down` |
| `make test` | `task test:run` or `task test` |
| `make test PHP=8.2` | `task test:run PHP=8.2` |
| `make shell` | `task dev:shell` or `task shell` |
| `make install` | `task dev:install` |
| `make l9` | `task l9` |
| `make test-all` | `task test:matrix` |
| `make clean` | `task docker:clean` |

---

## Further Reading

- [Task Official Documentation](https://taskfile.dev/)
- [Task GitHub Repository](https://github.com/go-task/task)
- [Task Examples](https://taskfile.dev/usage/)

---

## Support

For issues or questions:
1. Check this documentation
2. Run `task --list` for available tasks
3. Run `task dev:info` for environment information
4. Check the Taskfile source code in `Taskfile.yml` and `taskfiles/`

