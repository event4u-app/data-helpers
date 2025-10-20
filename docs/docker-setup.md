# Docker Setup for Local Testing

This Docker setup allows you to run tests locally with multiple PHP versions (8.2, 8.3, 8.4) and easily switch between them.

## Prerequisites

- Docker and Docker Compose installed
- Make sure Docker is running

## Quick Start

### 1. Build and start containers

```bash
make up
```

or

```bash
docker-compose up -d --build
```

This will build and start three containers with PHP, Composer, and Task installed:
- `data-helpers-php82` (PHP 8.2)
- `data-helpers-php83` (PHP 8.3)
- `data-helpers-php84` (PHP 8.4)

**Note:** If you've updated the Dockerfile, use `--build` to rebuild the containers.

### 2. Install dependencies

```bash
make install
```

or

```bash
./docker/test.sh -p 8.2 -i
./docker/test.sh -p 8.3 -i
./docker/test.sh -p 8.4 -i
```

### 3. Run tests

```bash
# Test with PHP 8.3 and Laravel 11
make test PHP=8.3 FW=l V=11

# Or directly with the script
./docker/test.sh -p 8.3 -l 11

# All tests from test matrix
make test-all
```

## Usage Options

You have three ways to run tests:

1. **Makefile** (recommended) - Simple, short commands
2. **Scripts** - More control and options
3. **Direct Docker** - Full control

### Option 1: With Makefile (Recommended)

```bash
# Show help
make help

# Start containers
make up

# Open shell in container
make shell PHP=8.2

# Run tests
make test PHP=8.3 FW=l V=11
make test-phpstan PHP=8.4 FW=s V=7

# All tests
make test-all
make test-all-phpstan

# Specific tests
make test-php82        # All PHP 8.2 tests
make test-laravel      # All Laravel tests

# Quick shortcuts
make l9                # Laravel 9 with PHP 8.2
make l11               # Laravel 11 with PHP 8.4
make s7                # Symfony 7 with PHP 8.4

# Cleanup
make clean
make rebuild
```

### Option 2: With Scripts

#### Test with Specific PHP Version and Framework

Use the `docker/test.sh` script:

```bash
# Test with PHP 8.2 and Laravel 9
./docker/test.sh -p 8.2 -l 9

# Test with PHP 8.3 and Laravel 11
./docker/test.sh -p 8.3 -l 11

# Test with PHP 8.4 and Symfony 7
./docker/test.sh -p 8.4 -s 7

# Test with PHP 8.2 and Doctrine 3
./docker/test.sh -p 8.2 -d 3

# Test with PHPStan
./docker/test.sh -p 8.3 -l 11 --phpstan

# Install dependencies and run tests
./docker/test.sh -p 8.2 -l 9 -i

# Open shell
./docker/test.sh -p 8.4 --shell
```

**Available Options:**
- `-p, --php VERSION` - PHP version (8.2, 8.3, or 8.4) [default: 8.4]
- `-l, --laravel VERSION` - Test with Laravel version (9, 10, or 11)
- `-s, --symfony VERSION` - Test with Symfony version (6 or 7)
- `-d, --doctrine VERSION` - Test with Doctrine ORM version (2 or 3)
- `--phpstan` - Run PHPStan after tests
- `--no-tests` - Skip running tests
- `-i, --install` - Install dependencies before running tests
- `--shell` - Open a shell in the container
- `-h, --help` - Display help message

#### Run All Tests from Test Matrix

Use the `docker/test-all.sh` script:

```bash
# Run all tests
./docker/test-all.sh

# Run all tests for PHP 8.2
./docker/test-all.sh -p 8.2

# Run all Laravel tests
./docker/test-all.sh -f laravel

# Run all PHP 8.3 Symfony tests
./docker/test-all.sh -p 8.3 -f symfony

# Run all tests with PHPStan
./docker/test-all.sh --phpstan

# Install dependencies and run all tests
./docker/test-all.sh -i
```

**Available Options:**
- `-p, --php VERSION` - Only test with specific PHP version (8.2, 8.3, or 8.4)
- `-f, --framework NAME` - Only test with specific framework (laravel, symfony, doctrine)
- `--phpstan` - Run PHPStan after each test
- `-i, --install` - Install dependencies before running tests
- `-h, --help` - Display help message

### Option 3: Direct Docker Commands

```bash
# Run tests in PHP 8.2 container
docker exec data-helpers-php82 task test:unit

# Run PHPStan in PHP 8.3 container
docker exec data-helpers-php83 task quality:phpstan

# Run test-with-versions script in PHP 8.4 container
docker exec data-helpers-php84 ./scripts/test-with-versions.sh -l 11

# Open shell in PHP 8.2 container
docker exec -it data-helpers-php82 /bin/bash
```

## Test Matrix

Based on `.github/workflows/run-tests.yml`:

### PHP 8.2
- Laravel 9, 10, 11
- Symfony 6, 7
- Doctrine 2, 3

### PHP 8.3
- Laravel 10, 11 (Laravel 9 not compatible)
- Symfony 6, 7
- Doctrine 2, 3

### PHP 8.4
- Laravel 11 (Laravel 9, 10 not compatible)
- Symfony 6, 7
- Doctrine 2, 3

## Available Containers

- `data-helpers-php82` - PHP 8.2
- `data-helpers-php83` - PHP 8.3
- `data-helpers-php84` - PHP 8.4

Each container has:
- Own Composer cache (faster dependency installation)
- Own vendor directory
- All required PHP extensions (zip, intl, xml, mbstring, pdo, pdo_mysql, bcmath)

## Container Management

### Start Containers

```bash
docker-compose up -d
# or
make up
```

### Stop Containers

```bash
docker-compose down
# or
make down
```

### Restart Containers

```bash
docker-compose restart
# or
make restart
```

### Rebuild Containers

```bash
docker-compose build
docker-compose up -d
# or
make rebuild
```

### View Container Logs

```bash
docker-compose logs php82
docker-compose logs php83
docker-compose logs php84
# or
make logs
```

### Remove Containers and Volumes

```bash
docker-compose down -v
# or
make clean
```

## Troubleshooting

### Container Not Starting

Check if Docker is running:

```bash
docker info
```

Check container logs:

```bash
docker-compose logs php82
```

Rebuild containers:

```bash
make rebuild
```

### Permission Issues

If you encounter permission issues with vendor or cache directories:

```bash
docker exec data-helpers-php82 chmod -R 777 vendor
```

### Composer Cache

Each PHP version has its own Composer cache volume to speed up dependency installation:
- `composer-cache-82` for PHP 8.2
- `composer-cache-83` for PHP 8.3
- `composer-cache-84` for PHP 8.4

To clear the cache:

```bash
docker volume rm data-helpers_composer-cache-82
docker volume rm data-helpers_composer-cache-83
docker volume rm data-helpers_composer-cache-84
```

Or use:

```bash
make clean
```

### Tests Failing

1. Make sure dependencies are installed:
   ```bash
   make install
   ```

2. Try rebuilding containers:
   ```bash
   make rebuild
   ```

3. Check if the PHP/framework combination is supported (see Test Matrix above)

## Tips

1. **Use the Makefile** for quick commands - it's the easiest way
2. **Use the scripts** instead of direct Docker commands - they handle container management automatically
3. **Install dependencies once** per PHP version and reuse them for multiple test runs
4. **Use `--shell` mode** for debugging or running multiple commands
5. **Run `test-all.sh`** to verify all combinations work before pushing changes
6. **Each PHP version has its own vendor directory** in the container, so you can switch between versions without conflicts

## Examples

### Quick Testing

```bash
# Quick test with PHP 8.3 and Laravel 11
make l11

# Quick test with Symfony 7
make s7
```

### Full Test Suite

```bash
# Full test suite for PHP 8.2
make test-all-phpstan -p 8.2

# All tests before push
make test-all -i
```

### Debugging

```bash
# Debug in PHP 8.4 container
make shell PHP=8.4

# Inside container:
composer install
./scripts/test-with-versions.sh -l 11 -p
vendor/bin/pest --filter=MyTest
exit
```

### Development Workflow

```bash
# 1. Start environment
make up

# 2. Install dependencies
make install

# 3. Run tests during development
make test PHP=8.4 FW=l V=11

# 4. Run quality checks
make test-phpstan PHP=8.4 FW=l V=11

# 5. Before pushing - test everything
make test-all
```

## Further Information

- [Taskfile Guide](taskfile-guide.md) - Using Task runner instead of Make
- [Test with Versions](test-with-versions.md) - Details about the test-with-versions script
- [Scripts](scripts.md) - Overview of all available scripts
