#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/../.." && pwd)"

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}  Data Helpers - E2E Tests${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Function to run E2E tests for a framework
run_e2e_tests() {
    local framework=$1
    local dir="$SCRIPT_DIR/$framework"

    echo -e "${YELLOW}📦  Setting up $framework E2E tests...${NC}"

    # Check if directory exists
    if [ ! -d "$dir" ]; then
        echo -e "${RED}❌  Directory not found: $dir${NC}"
        return 1
    fi

    cd "$dir"

    # Install dependencies if needed
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}📥  Installing dependencies...${NC}"
        composer install --no-interaction --prefer-dist --quiet
    fi

    # Setup Laravel-specific requirements
    if [ "$framework" = "Laravel" ]; then
        # Create database directory if it doesn't exist
        mkdir -p database

        # Create SQLite database if it doesn't exist
        if [ ! -f "database/database.sqlite" ]; then
            echo -e "${YELLOW}🗄️  Creating SQLite database...${NC}"
            touch database/database.sqlite
        fi

        # Run migrations if cache table doesn't exist
        if ! php artisan migrate:status --quiet 2>/dev/null | grep -q "create_cache_table"; then
            echo -e "${YELLOW}📋  Setting up cache table...${NC}"
            php artisan cache:table --quiet 2>/dev/null || true
            php artisan migrate --force --quiet
        fi
    fi

    # Run tests
    echo -e "${YELLOW}🧪  Running $framework E2E tests...${NC}"

    # Run tests and capture output
    TEST_OUTPUT=$(vendor/bin/pest --colors=always 2>&1)
    TEST_EXIT_CODE=$?

    # Extract test statistics
    STATS=$(echo "$TEST_OUTPUT" | grep "Tests:" | tail -1)

    if [ $TEST_EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}✅  $framework E2E tests passed!${NC}"
        echo -e "${GREEN}      $STATS${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}❌  $framework E2E tests failed!${NC}"
        echo -e "${RED}      $STATS${NC}"
        echo ""
        # Show only failed test names (compact view)
        echo -e "${YELLOW}Failed tests:${NC}"
        echo "$TEST_OUTPUT" | grep -E "FAIL|⨯" | head -20 || true
        echo ""
        return 1
    fi
}

# Track failures
FAILED=0

# Run Laravel E2E tests
if ! run_e2e_tests "Laravel"; then
    FAILED=$((FAILED + 1))
fi

# Run Symfony E2E tests
if ! run_e2e_tests "Symfony"; then
    FAILED=$((FAILED + 1))
fi

# Return to root directory
cd "$ROOT_DIR"

# Summary
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}✅  All E2E tests passed!${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    exit 0
else
    echo -e "${RED}❌  $FAILED framework(s) failed E2E tests${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    exit 1
fi

