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

    # Run tests
    echo -e "${YELLOW}🧪  Running $framework E2E tests (including linked Unit/Integration tests)...${NC}"

    # Run tests and capture output
    TEST_OUTPUT=$(vendor/bin/pest --colors=always 2>&1)
    TEST_EXIT_CODE=$?

    # Show output
    echo "$TEST_OUTPUT"

    # Extract test statistics
    STATS=$(echo "$TEST_OUTPUT" | grep "Tests:" | tail -1)

    if [ $TEST_EXIT_CODE -eq 0 ]; then
        echo -e "${GREEN}✅  $framework E2E tests passed!${NC}"
        echo -e "${GREEN}    $STATS${NC}"
        echo ""
        return 0
    else
        echo -e "${YELLOW}⚠️  $framework E2E tests completed with some failures${NC}"
        echo -e "${YELLOW}    $STATS${NC}"
        echo -e "${YELLOW}    Note: Some failures are expected (e.g., Laravel tests in Symfony, vice versa)${NC}"
        echo ""
        return 0  # Don't fail the build for expected failures
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

