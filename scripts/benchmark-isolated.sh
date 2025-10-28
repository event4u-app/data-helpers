#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Default values
PHP_VERSION="8.4"

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Run comprehensive benchmarks in an isolated temporary container."
    echo "This ensures the local composer.json is not modified."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --php VERSION            PHP version to use (8.2, 8.3, or 8.4, default: 8.4)"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                       # Run benchmarks with PHP 8.4"
    echo "  $0 --php 8.2             # Run benchmarks with PHP 8.2"
    echo ""
    exit 0
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --php)
            PHP_VERSION="$2"
            shift 2
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
if [[ ! "$PHP_VERSION" =~ ^8\.[234]$ ]]; then
    echo -e "${RED}✗${NC}  Invalid PHP version: $PHP_VERSION"
    echo -e "   Valid versions: 8.2, 8.3, 8.4"
    exit 1
fi

# Get the image name based on PHP version
IMAGE_NAME="data-helpers-php${PHP_VERSION/./}"

# Check if the image exists
if ! docker image inspect "$IMAGE_NAME" &> /dev/null; then
    echo -e "${RED}✗${NC}  Docker image '$IMAGE_NAME' not found"
    echo -e "   Run: ${CYAN}task docker:build${NC} or ${CYAN}docker compose build${NC}"
    exit 1
fi

# Generate container name
CONTAINER_NAME="benchmark-isolated-php${PHP_VERSION/./}-$$"

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Isolated Benchmark in Temporary Container${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "PHP:        ${YELLOW}${PHP_VERSION}${NC}"
echo -e "Image:      ${YELLOW}${IMAGE_NAME}${NC}"
echo -e "Container:  ${YELLOW}${CONTAINER_NAME}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Create temporary directory for the benchmark
TEMP_DIR=$(mktemp -d)

echo -e "${BLUE}→${NC}  Preparing benchmark environment..."
echo -e "${YELLOW}  Temp directory: ${TEMP_DIR}${NC}"

# Copy project files to temp directory (excluding vendor, .git, etc.)
rsync -a \
    --exclude='vendor/' \
    --exclude='tests-e2e/*/vendor/' \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='.phpunit.cache/' \
    --exclude='.pest/' \
    --exclude='.event4u/' \
    --exclude='*.log' \
    "$(pwd)/" "$TEMP_DIR/"

# Ensure vendor directories are completely removed in temp directory
rm -rf "$TEMP_DIR/vendor"
rm -rf "$TEMP_DIR/tests-e2e/Laravel/vendor"
rm -rf "$TEMP_DIR/tests-e2e/Symfony/vendor"
rm -rf "$TEMP_DIR/.event4u"

echo -e "${GREEN}✓${NC}  Project files copied to temporary directory"

# Ensure network exists
if ! docker network inspect data-helpers &> /dev/null; then
    echo -e "${BLUE}→${NC}  Creating Docker network..."
    docker network create data-helpers &> /dev/null
    echo -e "${GREEN}✓${NC}  Network created"
fi

# Cleanup function
cleanup() {
    local exit_code=$?
    echo ""
    echo -e "${BLUE}→${NC}  Cleaning up..."

    # Stop and remove container
    if docker ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        docker rm -f "$CONTAINER_NAME" &> /dev/null || true
        echo -e "${GREEN}✓${NC}  Container removed"
    fi

    # Remove temporary directory
    if [[ -d "$TEMP_DIR" ]]; then
        rm -rf "$TEMP_DIR"
        echo -e "${GREEN}✓${NC}  Temporary directory removed"
    fi

    echo ""
    if [[ $exit_code -eq 0 ]]; then
        echo -e "${GREEN}✅  Benchmark completed successfully${NC}"
    else
        echo -e "${RED}❌  Benchmark failed${NC}"
    fi
    echo ""

    exit $exit_code
}

# Register cleanup function
trap cleanup EXIT INT TERM

# Start the container
echo -e "${BLUE}→${NC}  Starting temporary container..."

docker run --rm -d \
    --name "$CONTAINER_NAME" \
    --network data-helpers \
    -v "$TEMP_DIR:/app" \
    -w /app \
    "$IMAGE_NAME" \
    tail -f /dev/null

echo -e "${GREEN}✓${NC}  Container started"

# Install dependencies
echo ""
echo -e "${BLUE}→${NC}  Installing dependencies..."
docker exec "$CONTAINER_NAME" composer install --prefer-dist --no-interaction --quiet
echo -e "${GREEN}✓${NC}  Dependencies installed"

# Warm up cache (build DTOs once)
echo ""
echo -e "${BLUE}→${NC}  Warming up cache (building DTOs)..."
docker exec "$CONTAINER_NAME" php bin/warm-cache.php tests/Utils/SimpleDtos tests/Utils/Dtos
echo -e "${GREEN}✓${NC}  Cache warmed up"

# Run comprehensive benchmarks
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Running Comprehensive Benchmarks${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

docker exec "$CONTAINER_NAME" php scripts/comprehensive-benchmark.php

# Copy updated documentation back to host
echo ""
echo -e "${BLUE}→${NC}  Copying updated documentation..."
docker cp "$CONTAINER_NAME:/app/starlight/src/content/docs/performance/benchmarks.md" \
    "$(pwd)/starlight/src/content/docs/performance/benchmarks.md"
echo -e "${GREEN}✓${NC}  Documentation updated"

# Cleanup will be handled by trap

