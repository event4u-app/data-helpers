#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Default values
PHP_VERSION="8.4"
FRAMEWORK=""
VERSION=""
RUN_TESTS=true
RUN_PHPSTAN=false
INSTALL_DEPS=false
SHELL_MODE=false

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Run tests in Docker containers with different PHP versions."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  -p, --php VERSION        PHP version to use (8.2, 8.3, or 8.4) [default: 8.4]"
    echo "  -l, --laravel VERSION    Test with Laravel version (9, 10, or 11)"
    echo "  -s, --symfony VERSION    Test with Symfony version (6 or 7)"
    echo "  -d, --doctrine VERSION   Test with Doctrine ORM version (2 or 3)"
    echo "  --phpstan                Run PHPStan after tests"
    echo "  --no-tests               Skip running tests"
    echo "  -i, --install            Install dependencies before running tests"
    echo "  --shell                  Open a shell in the container"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 -p 8.2 -l 9           # Test with PHP 8.2 and Laravel 9"
    echo "  $0 -p 8.3 -l 11 --phpstan # Test with PHP 8.3, Laravel 11 and run PHPStan"
    echo "  $0 -p 8.4 -s 7           # Test with PHP 8.4 and Symfony 7"
    echo "  $0 -p 8.2 --shell        # Open shell in PHP 8.2 container"
    echo "  $0 -p 8.3 -l 10 -i       # Install deps and test with PHP 8.3 and Laravel 10"
    echo ""
    echo -e "${CYAN}Documentation:${NC}"
    echo "  docs/docker-setup.md     # Full Docker documentation"
    echo "  docs/taskfile-guide.md   # Task runner guide"
    echo ""
    exit 0
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -p|--php)
            PHP_VERSION="$2"
            shift 2
            ;;
        -l|--laravel)
            FRAMEWORK="laravel"
            VERSION="$2"
            shift 2
            ;;
        -s|--symfony)
            FRAMEWORK="symfony"
            VERSION="$2"
            shift 2
            ;;
        -d|--doctrine)
            FRAMEWORK="doctrine"
            VERSION="$2"
            shift 2
            ;;
        --phpstan)
            RUN_PHPSTAN=true
            shift
            ;;
        --no-tests)
            RUN_TESTS=false
            shift
            ;;
        -i|--install)
            INSTALL_DEPS=true
            shift
            ;;
        --shell)
            SHELL_MODE=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo -e "${RED}Error:${NC} Unknown option: $1"
            usage
            ;;
    esac
done

# Validate PHP version
if [[ ! "$PHP_VERSION" =~ ^(8\.2|8\.3|8\.4)$ ]]; then
    echo -e "${RED}Error:${NC} Invalid PHP version. Must be 8.2, 8.3, or 8.4."
    exit 1
fi

# Map PHP version to container name
PHP_VERSION_SHORT=$(echo "$PHP_VERSION" | tr -d '.')
CONTAINER_NAME="data-helpers-php${PHP_VERSION_SHORT}"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error:${NC} Docker is not running. Please start Docker first."
    exit 1
fi

# Check if container exists, if not build it
if ! docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${BLUE}→${NC} Building Docker container for PHP ${PHP_VERSION}..."
    docker-compose build "php${PHP_VERSION_SHORT}"
fi

# Start container if not running
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
    echo -e "${BLUE}→${NC} Starting Docker container for PHP ${PHP_VERSION}..."
    docker-compose up -d "php${PHP_VERSION_SHORT}"
    sleep 2
fi

# Shell mode
if [[ "$SHELL_MODE" == true ]]; then
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  Opening shell in PHP ${PHP_VERSION} container                    ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    docker exec -it "$CONTAINER_NAME" /bin/bash
    exit 0
fi

# Display configuration
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}  Testing event4u/data-helpers in Docker                    ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}PHP Version:${NC} $PHP_VERSION"
echo -e "${YELLOW}Container:${NC} $CONTAINER_NAME"

if [[ -n "$FRAMEWORK" ]]; then
    echo -e "${YELLOW}Framework:${NC} $FRAMEWORK $VERSION"
fi

echo -e "${YELLOW}Install Dependencies:${NC} $INSTALL_DEPS"
echo -e "${YELLOW}Run Tests:${NC} $RUN_TESTS"
echo -e "${YELLOW}Run PHPStan:${NC} $RUN_PHPSTAN"
echo ""

# Install dependencies if requested
if [[ "$INSTALL_DEPS" == true ]]; then
    echo -e "${BLUE}→${NC} Installing dependencies..."
    docker exec "$CONTAINER_NAME" composer install --prefer-dist --no-interaction
    echo -e "${GREEN}✅  ${NC}Dependencies installed"
    echo ""
fi

# Build test command
if [[ -n "$FRAMEWORK" ]]; then
    TEST_CMD="./scripts/test-with-versions.sh -${FRAMEWORK:0:1} $VERSION"

    if [[ "$RUN_PHPSTAN" == true ]]; then
        TEST_CMD="$TEST_CMD -p"
    fi

    if [[ "$RUN_TESTS" == false ]]; then
        TEST_CMD="$TEST_CMD --no-tests"
    fi

    echo -e "${BLUE}→${NC} Running: ${TEST_CMD}"
    echo ""
    docker exec -it "$CONTAINER_NAME" bash -c "$TEST_CMD"
else
    # Run standard tests
    if [[ "$RUN_TESTS" == true ]]; then
        echo -e "${BLUE}→${NC} Running tests..."
        echo ""
        docker exec -it "$CONTAINER_NAME" composer test
        echo ""
        echo -e "${GREEN}✅  ${NC}Tests passed!"
    fi

    if [[ "$RUN_PHPSTAN" == true ]]; then
        echo ""
        echo -e "${BLUE}→${NC} Running PHPStan..."
        echo ""
        docker exec -it "$CONTAINER_NAME" composer phpstan
        echo ""
        echo -e "${GREEN}✅  ${NC}PHPStan passed!"
    fi
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  All checks completed successfully! 🎉                     ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"

