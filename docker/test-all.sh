#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Test matrix based on .github/workflows/run-tests.yml
declare -A TEST_MATRIX=(
    # PHP 8.2
    ["8.2-laravel-9"]="8.2 laravel 9"
    ["8.2-laravel-10"]="8.2 laravel 10"
    ["8.2-laravel-11"]="8.2 laravel 11"
    ["8.2-symfony-6"]="8.2 symfony 6"
    ["8.2-symfony-7"]="8.2 symfony 7"
    ["8.2-doctrine-2"]="8.2 doctrine 2"
    ["8.2-doctrine-3"]="8.2 doctrine 3"

    # PHP 8.3
    ["8.3-laravel-10"]="8.3 laravel 10"
    ["8.3-laravel-11"]="8.3 laravel 11"
    ["8.3-symfony-6"]="8.3 symfony 6"
    ["8.3-symfony-7"]="8.3 symfony 7"
    ["8.3-doctrine-2"]="8.3 doctrine 2"
    ["8.3-doctrine-3"]="8.3 doctrine 3"

    # PHP 8.4
    ["8.4-laravel-11"]="8.4 laravel 11"
    ["8.4-symfony-6"]="8.4 symfony 6"
    ["8.4-symfony-7"]="8.4 symfony 7"
    ["8.4-doctrine-2"]="8.4 doctrine 2"
    ["8.4-doctrine-3"]="8.4 doctrine 3"
)

# Default values
RUN_PHPSTAN=false
INSTALL_DEPS=false
SELECTED_PHP=""
SELECTED_FRAMEWORK=""

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Run all tests from the test matrix in Docker containers."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  -p, --php VERSION        Only test with specific PHP version (8.2, 8.3, or 8.4)"
    echo "  -f, --framework NAME     Only test with specific framework (laravel, symfony, doctrine)"
    echo "  --phpstan                Run PHPStan after each test"
    echo "  -i, --install            Install dependencies before running tests"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                       # Run all tests from matrix"
    echo "  $0 -p 8.2                # Run all PHP 8.2 tests"
    echo "  $0 -f laravel            # Run all Laravel tests"
    echo "  $0 -p 8.3 -f symfony     # Run PHP 8.3 with Symfony tests"
    echo "  $0 --phpstan             # Run all tests with PHPStan"
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
            SELECTED_PHP="$2"
            shift 2
            ;;
        -f|--framework)
            SELECTED_FRAMEWORK="$2"
            shift 2
            ;;
        --phpstan)
            RUN_PHPSTAN=true
            shift
            ;;
        -i|--install)
            INSTALL_DEPS=true
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

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error:${NC} Docker is not running. Please start Docker first."
    exit 1
fi

# Build all containers
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}  Building Docker containers...                             ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
docker-compose build
echo ""

# Start all containers
echo -e "${BLUE}→${NC} Starting all containers..."
docker-compose up -d
sleep 3
echo ""

# Install dependencies if requested
if [[ "$INSTALL_DEPS" == true ]]; then
    echo -e "${BLUE}→${NC} Installing dependencies in all containers..."
    for php_version in 8.2 8.3 8.4; do
        php_short=$(echo "$php_version" | tr -d '.')
        container="data-helpers-php${php_short}"
        echo -e "${YELLOW}  Installing in PHP ${php_version}...${NC}"
        docker exec "$container" composer install --prefer-dist --no-interaction
    done
    echo -e "${GREEN}✅  ${NC}Dependencies installed in all containers"
    echo ""
fi

# Run tests
TOTAL_TESTS=0
PASSED_TESTS=0
FAILED_TESTS=0
declare -a FAILED_TEST_NAMES

echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  Running Test Matrix                                       ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

for test_key in "${!TEST_MATRIX[@]}"; do
    read -r php_version framework version <<< "${TEST_MATRIX[$test_key]}"

    # Filter by PHP version if specified
    if [[ -n "$SELECTED_PHP" && "$php_version" != "$SELECTED_PHP" ]]; then
        continue
    fi

    # Filter by framework if specified
    if [[ -n "$SELECTED_FRAMEWORK" && "$framework" != "$SELECTED_FRAMEWORK" ]]; then
        continue
    fi

    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}Test ${TOTAL_TESTS}:${NC} PHP ${php_version} - ${framework} ${version}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    # Build test command
    PHPSTAN_FLAG=""
    if [[ "$RUN_PHPSTAN" == true ]]; then
        PHPSTAN_FLAG="--phpstan"
    fi

    if ./docker/test.sh -p "$php_version" -"${framework:0:1}" "$version" $PHPSTAN_FLAG; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
        echo -e "${GREEN}✅  ${NC}Test passed: PHP ${php_version} - ${framework} ${version}"
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
        FAILED_TEST_NAMES+=("PHP ${php_version} - ${framework} ${version}")
        echo -e "${RED}❌  ${NC}Test failed: PHP ${php_version} - ${framework} ${version}"
    fi

    echo ""
done

# Summary
echo ""
echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║${NC}  Test Summary                                              ${CYAN}║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${BLUE}Total Tests:${NC}   $TOTAL_TESTS"
echo -e "${GREEN}Passed:${NC}        $PASSED_TESTS"
echo -e "${RED}Failed:${NC}        $FAILED_TESTS"
echo ""

if [[ $FAILED_TESTS -gt 0 ]]; then
    echo -e "${RED}Failed Tests:${NC}"
    for test_name in "${FAILED_TEST_NAMES[@]}"; do
        echo -e "  ${RED}✗${NC} $test_name"
    done
    echo ""
    exit 1
else
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║${NC}  All tests passed successfully! 🎉                        ${GREEN}║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 0
fi

