#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Comprehensive test matrix with isolated framework tests
# Using parallel arrays for compatibility with Bash 3.2
TEST_MATRIX_KEYS=(
    # Plain PHP (no frameworks) - all PHP versions
    "8.2-plain"
    "8.3-plain"
    "8.4-plain"

    # Laravel isolated tests
    "8.2-laravel-9"
    "8.2-laravel-10"
    "8.2-laravel-11"
    "8.3-laravel-10"
    "8.3-laravel-11"
    "8.4-laravel-11"

    # Symfony isolated tests
    "8.2-symfony-6"
    "8.2-symfony-7"
    "8.3-symfony-6"
    "8.3-symfony-7"
    "8.4-symfony-6"
    "8.4-symfony-7"

    # Doctrine isolated tests
    "8.2-doctrine-2"
    "8.2-doctrine-3"
    "8.3-doctrine-2"
    "8.3-doctrine-3"
    "8.4-doctrine-2"
    "8.4-doctrine-3"
)

TEST_MATRIX_VALUES=(
    # Plain PHP (no frameworks) - all PHP versions
    "8.2 plain"
    "8.3 plain"
    "8.4 plain"

    # Laravel isolated tests
    "8.2 laravel 9"
    "8.2 laravel 10"
    "8.2 laravel 11"
    "8.3 laravel 10"
    "8.3 laravel 11"
    "8.4 laravel 11"

    # Symfony isolated tests
    "8.2 symfony 6"
    "8.2 symfony 7"
    "8.3 symfony 6"
    "8.3 symfony 7"
    "8.4 symfony 6"
    "8.4 symfony 7"

    # Doctrine isolated tests
    "8.2 doctrine 2"
    "8.2 doctrine 3"
    "8.3 doctrine 2"
    "8.3 doctrine 3"
    "8.4 doctrine 2"
    "8.4 doctrine 3"
)

# Default values
RUN_PHPSTAN=false
INSTALL_DEPS=false
SELECTED_PHP=""
SELECTED_FRAMEWORK=""
SELECTED_VERSION=""

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Run comprehensive test matrix with isolated framework tests."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  -p, --php VERSION        Only test with specific PHP version (8.2, 8.3, or 8.4)"
    echo "  -f, --framework NAME     Only test with specific framework (plain, laravel, symfony, doctrine)"
    echo "  -v, --version VERSION    Only test with specific framework version"
    echo "  --phpstan                Run PHPStan after each test"
    echo "  -i, --install            Install dependencies before running tests"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0                       # Run all tests from matrix"
    echo "  $0 -p 8.2                # Run all PHP 8.2 tests"
    echo "  $0 -f plain              # Run all plain PHP tests"
    echo "  $0 -f laravel            # Run all Laravel tests (isolated)"
    echo "  $0 -f laravel -v 11      # Run all Laravel 11 tests"
    echo "  $0 -p 8.3 -f symfony     # Run PHP 8.3 with Symfony tests"
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
        -v|--version)
            SELECTED_VERSION="$2"
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
echo -e "${BLUE}🔨  Building Docker containers...${NC}"
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

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Running Comprehensive Test Matrix (Isolated)${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

for i in "${!TEST_MATRIX_KEYS[@]}"; do
    test_key="${TEST_MATRIX_KEYS[$i]}"
    test_value="${TEST_MATRIX_VALUES[$i]}"
    read -r php_version framework version <<< "$test_value"

    # Handle plain PHP (no version)
    if [[ "$framework" == "plain" ]]; then
        version=""
    fi

    # Filter by PHP version if specified
    if [[ -n "$SELECTED_PHP" && "$php_version" != "$SELECTED_PHP" ]]; then
        continue
    fi

    # Filter by framework if specified
    if [[ -n "$SELECTED_FRAMEWORK" && "$framework" != "$SELECTED_FRAMEWORK" ]]; then
        continue
    fi

    # Filter by version if specified
    if [[ -n "$SELECTED_VERSION" && "$version" != "$SELECTED_VERSION" ]]; then
        continue
    fi

    TOTAL_TESTS=$((TOTAL_TESTS + 1))

    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    if [[ "$framework" == "plain" ]]; then
        echo -e "${YELLOW}Test ${TOTAL_TESTS}:${NC} PHP ${php_version} - Plain (no frameworks)"
    else
        echo -e "${YELLOW}Test ${TOTAL_TESTS}:${NC} PHP ${php_version} - ${framework} ${version} (isolated)"
    fi
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""

    # Run test using test-isolated.sh script
    TEST_RESULT=0

    # Build command for test-isolated.sh
    if [[ "$framework" == "plain" ]]; then
        TEST_CMD="./scripts/test-isolated.sh --plain --php $php_version"
    elif [[ "$framework" == "laravel" ]]; then
        TEST_CMD="./scripts/test-isolated.sh --laravel $version --php $php_version"
    elif [[ "$framework" == "symfony" ]]; then
        TEST_CMD="./scripts/test-isolated.sh --symfony $version --php $php_version"
    elif [[ "$framework" == "doctrine" ]]; then
        TEST_CMD="./scripts/test-isolated.sh --doctrine $version --php $php_version"
    fi

    # Add PHPStan flag if requested
    if [[ "$RUN_PHPSTAN" == true ]]; then
        TEST_CMD="$TEST_CMD --phpstan"
    fi

    # Run test-isolated.sh and show output
    if $TEST_CMD; then
        TEST_RESULT=0
    else
        TEST_RESULT=$?
    fi

    # Check result
    if [[ $TEST_RESULT -eq 0 ]]; then
        PASSED_TESTS=$((PASSED_TESTS + 1))
        if [[ "$framework" == "plain" ]]; then
            echo -e "${GREEN}✅  ${NC}Test passed: PHP ${php_version} - Plain"
        else
            echo -e "${GREEN}✅  ${NC}Test passed: PHP ${php_version} - ${framework} ${version}"
        fi
    else
        FAILED_TESTS=$((FAILED_TESTS + 1))
        if [[ "$framework" == "plain" ]]; then
            FAILED_TEST_NAMES+=("PHP ${php_version} - Plain")
            echo -e "${RED}❌  ${NC}Test failed: PHP ${php_version} - Plain"
        else
            FAILED_TEST_NAMES+=("PHP ${php_version} - ${framework} ${version}")
            echo -e "${RED}❌  ${NC}Test failed: PHP ${php_version} - ${framework} ${version}"
        fi
    fi

    echo ""
done

# Summary
echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Test Summary${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
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
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${GREEN}✅  All tests passed successfully! 🎉${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    exit 0
fi

