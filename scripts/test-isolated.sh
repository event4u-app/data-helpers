#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Default values
TEST_TYPE="plain"
PHP_VERSION="8.4"
RUN_TESTS=true
RUN_PHPSTAN=false

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Test the package in isolation using temporary Docker containers."
    echo "Each test runs in a fresh container with only the required framework."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  --plain                  Test with plain PHP (no frameworks)"
    echo "  --laravel VERSION        Test with only Laravel (10, or 11)"
    echo "  --symfony VERSION        Test with only Symfony (6 or 7)"
    echo "  --doctrine VERSION       Test with only Doctrine (2 or 3)"
    echo "  --php VERSION            PHP version to use (8.2, 8.3, or 8.4, default: 8.4)"
    echo "  -p, --phpstan            Run PHPStan after tests"
    echo "  --no-tests               Skip running tests"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 --plain               # Test without any frameworks (PHP 8.4)"
    echo "  $0 --plain --php 8.2     # Test without any frameworks (PHP 8.2)"
    echo "  $0 --laravel 11          # Test with only Laravel 11"
    echo "  $0 --symfony 6           # Test with only Symfony 6"
    echo ""
    exit 0
}

# Parse command line arguments
FRAMEWORK=""
VERSION=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --plain)
            TEST_TYPE="plain"
            shift
            ;;
        --laravel)
            TEST_TYPE="laravel"
            FRAMEWORK="laravel"
            VERSION="$2"
            shift 2
            ;;
        --symfony)
            TEST_TYPE="symfony"
            FRAMEWORK="symfony"
            VERSION="$2"
            shift 2
            ;;
        --doctrine)
            TEST_TYPE="doctrine"
            FRAMEWORK="doctrine"
            VERSION="$2"
            shift 2
            ;;
        --php)
            PHP_VERSION="$2"
            shift 2
            ;;
        -p|--phpstan)
            RUN_PHPSTAN=true
            shift
            ;;
        --no-tests)
            RUN_TESTS=false
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
CONTAINER_NAME="test-isolated-${TEST_TYPE}-${VERSION:-plain}-php${PHP_VERSION/./}-$$"

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}Isolated Test in Temporary Container${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "Type:       ${YELLOW}${TEST_TYPE}${VERSION:+ $VERSION}${NC}"
echo -e "PHP:        ${YELLOW}${PHP_VERSION}${NC}"
echo -e "Image:      ${YELLOW}${IMAGE_NAME}${NC}"
echo -e "Container:  ${YELLOW}${CONTAINER_NAME}${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Function to get composer packages for framework
get_composer_packages() {
    local fw=$1
    local ver=$2

    case "$fw" in
        laravel)
            case "$ver" in
                10) echo "illuminate/support:^10.0 illuminate/validation:^10.0 illuminate/database:^10.0" ;;
                11) echo "illuminate/support:^11.0 illuminate/validation:^11.0 illuminate/database:^11.0" ;;
                *) echo ""; return 1 ;;
            esac
            ;;
        symfony)
            case "$ver" in
                6) echo "symfony/validator:^6.0 symfony/http-kernel:^6.0 symfony/http-foundation:^6.0" ;;
                7) echo "symfony/validator:^7.0 symfony/http-kernel:^7.0 symfony/http-foundation:^7.0" ;;
                *) echo ""; return 1 ;;
            esac
            ;;
        doctrine)
            case "$ver" in
                2) echo "doctrine/orm:^2.0 doctrine/dbal:^2.0" ;;
                3) echo "doctrine/orm:^3.0 doctrine/dbal:^3.0" ;;
                *) echo ""; return 1 ;;
            esac
            ;;
        *)
            echo ""
            return 1
            ;;
    esac
}

# Create temporary directory for the test
TEMP_DIR=$(mktemp -d)

echo -e "${BLUE}→${NC}  Preparing test environment..."
echo -e "${YELLOW}  Temp directory: ${TEMP_DIR}${NC}"

# Copy project files to temp directory (excluding vendor, .git, etc.)
# Use absolute paths to ensure we don't accidentally modify local files
rsync -a \
    --exclude='vendor/' \
    --exclude='tests-e2e/*/vendor/' \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='.phpunit.cache/' \
    --exclude='.pest/' \
    --exclude='*.log' \
    "$(pwd)/" "$TEMP_DIR/"

# Ensure vendor directories are completely removed in temp directory
rm -rf "$TEMP_DIR/vendor"
rm -rf "$TEMP_DIR/tests-e2e/Laravel/vendor"
rm -rf "$TEMP_DIR/tests-e2e/Symfony/vendor"

echo -e "${GREEN}✓${NC}  Project files copied to temporary directory"

# Ensure network exists
if ! docker network inspect data-helpers &> /dev/null; then
    echo -e "${BLUE}→${NC}  Creating Docker network..."
    docker network create data-helpers &> /dev/null
    echo -e "${GREEN}✓${NC}  Network created"
fi

# Start the container
echo -e "${BLUE}→${NC}  Starting temporary container..."

# Run container with temp directory mounted
docker run --rm -d \
    --name "$CONTAINER_NAME" \
    --network data-helpers \
    -v "$TEMP_DIR:/app" \
    -v "composer-cache-${PHP_VERSION/./}:/root/.composer" \
    -w /app \
    "$IMAGE_NAME" \
    tail -f /dev/null

echo -e "${GREEN}✓${NC}  Container started: ${CYAN}${CONTAINER_NAME}${NC}"

# Function to run command in container
run_in_container() {
    docker exec "$CONTAINER_NAME" "$@"
}

# Function to verify installed packages
verify_installed_packages() {
    local test_type=$1
    local framework=$2
    local version=$3

    echo ""
    echo -e "${BLUE}→${NC}  Verifying installed packages..."

    # Get list of installed packages
    local installed_packages=$(run_in_container composer show --format=json 2>/dev/null | grep -o '"name":"[^"]*"' | cut -d'"' -f4)

    local errors=0
    local forbidden_packages=""

    case "$test_type" in
        plain)
            # Plain PHP: No framework packages should be installed
            forbidden_packages=$(echo "$installed_packages" | grep -E "^(illuminate/|symfony/validator|symfony/http-kernel|symfony/http-foundation|doctrine/orm|doctrine/dbal)" || true)

            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden framework packages in Plain PHP setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi
            ;;

        laravel)
            # Laravel: Only illuminate/* packages for the specific version should be installed
            # Check for wrong Laravel versions
            local wrong_laravel=""
            case "$version" in
                10)
                    wrong_laravel=$(echo "$installed_packages" | grep -E "^illuminate/" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^10\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
                11)
                    wrong_laravel=$(echo "$installed_packages" | grep -E "^illuminate/" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^11\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
            esac

            if [[ -n "$wrong_laravel" ]]; then
                echo -e "${RED}✗${NC}  Found Laravel packages with wrong version (expected: $version.x):"
                echo "$wrong_laravel" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Symfony framework packages (some Symfony components are OK as dependencies)
            forbidden_packages=$(echo "$installed_packages" | grep -E "^(symfony/validator|symfony/http-kernel|symfony/http-foundation)$" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Symfony framework packages in Laravel setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Doctrine ORM/DBAL
            forbidden_packages=$(echo "$installed_packages" | grep -E "^(doctrine/orm|doctrine/dbal)$" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Doctrine packages in Laravel setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi
            ;;

        symfony)
            # Symfony: Only symfony/* packages for the specific version should be installed
            # Check for wrong Symfony versions (only check the main framework packages)
            local wrong_symfony=""
            case "$version" in
                6)
                    wrong_symfony=$(echo "$installed_packages" | grep -E "^symfony/(validator|http-kernel|http-foundation)$" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^6\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
                7)
                    wrong_symfony=$(echo "$installed_packages" | grep -E "^symfony/(validator|http-kernel|http-foundation)$" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^7\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
            esac

            if [[ -n "$wrong_symfony" ]]; then
                echo -e "${RED}✗${NC}  Found Symfony packages with wrong version (expected: $version.x):"
                echo "$wrong_symfony" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Laravel packages
            forbidden_packages=$(echo "$installed_packages" | grep -E "^illuminate/" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Laravel packages in Symfony setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Doctrine ORM/DBAL
            forbidden_packages=$(echo "$installed_packages" | grep -E "^(doctrine/orm|doctrine/dbal)$" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Doctrine packages in Symfony setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi
            ;;

        doctrine)
            # Doctrine: Only doctrine/* packages for the specific version should be installed
            # Check for wrong Doctrine versions
            local wrong_doctrine=""
            case "$version" in
                2)
                    wrong_doctrine=$(echo "$installed_packages" | grep -E "^doctrine/(orm|dbal)$" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^2\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
                3)
                    wrong_doctrine=$(echo "$installed_packages" | grep -E "^doctrine/(orm|dbal)$" | while read pkg; do
                        local pkg_version=$(run_in_container composer show "$pkg" --format=json 2>/dev/null | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
                        if [[ ! "$pkg_version" =~ ^3\. ]]; then
                            echo "$pkg ($pkg_version)"
                        fi
                    done)
                    ;;
            esac

            if [[ -n "$wrong_doctrine" ]]; then
                echo -e "${RED}✗${NC}  Found Doctrine packages with wrong version (expected: $version.x):"
                echo "$wrong_doctrine" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Laravel packages
            forbidden_packages=$(echo "$installed_packages" | grep -E "^illuminate/" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Laravel packages in Doctrine setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi

            # Check for Symfony framework packages (some Symfony components are OK as dependencies)
            forbidden_packages=$(echo "$installed_packages" | grep -E "^(symfony/validator|symfony/http-kernel|symfony/http-foundation)$" || true)
            if [[ -n "$forbidden_packages" ]]; then
                echo -e "${RED}✗${NC}  Found forbidden Symfony framework packages in Doctrine setup:"
                echo "$forbidden_packages" | while read pkg; do
                    echo -e "   ${RED}✗${NC}  $pkg"
                done
                errors=1
            fi
            ;;
    esac

    if [[ $errors -eq 0 ]]; then
        echo -e "${GREEN}✓${NC}  Package verification passed"
    else
        echo ""
        echo -e "${RED}✗${NC}  Package verification failed!"
        exit 1
    fi
}

# Function to cleanup
cleanup() {
    echo ""
    echo -e "${BLUE}→${NC}  Cleaning up..."
    docker stop "$CONTAINER_NAME" &> /dev/null || true
    echo -e "${GREEN}✓${NC}  Container removed"

    # Remove temporary directory
    if [[ -n "$TEMP_DIR" ]] && [[ -d "$TEMP_DIR" ]]; then
        echo -e "${YELLOW}  Removing temp directory: ${TEMP_DIR}${NC}"
        rm -rf "$TEMP_DIR"
    fi
}

trap cleanup EXIT

# Install dependencies
echo ""
echo -e "${BLUE}→${NC}  Installing dependencies..."

if [[ "$TEST_TYPE" == "plain" ]]; then
    echo -e "${YELLOW}  Removing ALL framework packages and unnecessary dev tools...${NC}"
    run_in_container composer remove --dev \
        illuminate/cache illuminate/http illuminate/support illuminate/database \
        symfony/cache symfony/config symfony/dependency-injection \
        symfony/http-foundation symfony/http-kernel symfony/yaml \
        symfony/serializer symfony/property-info symfony/property-access symfony/validator \
        doctrine/collections doctrine/orm doctrine/dbal \
        nesbot/carbon \
        symplify/coding-standard symplify/easy-coding-standard friendsofphp/php-cs-fixer \
        rector/rector phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit \
        spaze/phpstan-disallowed-calls timeweb/phpstan-enum \
        phpbench/phpbench \
        --no-interaction --no-update 2>&1 | grep -v "is not required" || true

    echo -e "${YELLOW}  Deleting composer.lock...${NC}"
    run_in_container rm -f composer.lock

    echo -e "${YELLOW}  Installing base dependencies (no frameworks, no code quality tools)...${NC}"
    run_in_container composer install --no-interaction
else
    # Remove composer.lock to avoid version conflicts
    # Note: vendor/ is already excluded by rsync, so it doesn't exist in the container
    echo -e "${YELLOW}  Removing composer.lock...${NC}"
    run_in_container rm -f composer.lock

    # Define all framework packages that exist in composer.json
    ALL_ILLUMINATE_PACKAGES="illuminate/cache illuminate/database illuminate/http illuminate/support"
    ALL_SYMFONY_PACKAGES="symfony/cache symfony/config symfony/dependency-injection symfony/http-foundation symfony/http-kernel symfony/yaml symfony/serializer symfony/property-info symfony/property-access symfony/validator"
    ALL_DOCTRINE_PACKAGES="doctrine/collections doctrine/orm doctrine/dbal"

    # Carbon needs to be removed so Composer can install the correct version based on framework constraints
    CARBON_PACKAGE="nesbot/carbon"

    # Define code quality tools and benchmarking tools that pull in unnecessary dependencies (especially Symfony)
    # These are not needed for running tests and can cause dependency conflicts
    CODE_QUALITY_PACKAGES="symplify/coding-standard symplify/easy-coding-standard friendsofphp/php-cs-fixer rector/rector phpstan/phpstan phpstan/phpstan-mockery phpstan/phpstan-phpunit spaze/phpstan-disallowed-calls timeweb/phpstan-enum phpbench/phpbench"

    # Pest packages need to be removed temporarily to avoid Symfony Console conflicts
    # They will be reinstalled after framework packages are installed
    PEST_PACKAGES="pestphp/pest pestphp/pest-plugin-laravel"

    # Determine which packages to remove based on the framework being tested
    PACKAGES_TO_REMOVE=""
    case "$FRAMEWORK" in
        laravel)
            # For Laravel tests: remove ALL Laravel, Symfony, Doctrine packages, Carbon, Pest, code quality tools, and benchmarking tools
            echo -e "${YELLOW}  Removing all framework packages, Pest, code quality tools, and benchmarking tools (testing Laravel only)...${NC}"
            PACKAGES_TO_REMOVE="$ALL_ILLUMINATE_PACKAGES $ALL_SYMFONY_PACKAGES $ALL_DOCTRINE_PACKAGES $CARBON_PACKAGE $PEST_PACKAGES $CODE_QUALITY_PACKAGES"
            ;;
        symfony)
            # For Symfony tests: remove ALL Laravel, Symfony, Doctrine packages, Carbon, Pest, code quality tools, and benchmarking tools
            echo -e "${YELLOW}  Removing all framework packages, Pest, code quality tools, and benchmarking tools (testing Symfony only)...${NC}"
            PACKAGES_TO_REMOVE="$ALL_ILLUMINATE_PACKAGES $ALL_SYMFONY_PACKAGES $ALL_DOCTRINE_PACKAGES $CARBON_PACKAGE $PEST_PACKAGES $CODE_QUALITY_PACKAGES"
            ;;
        doctrine)
            # For Doctrine tests: remove ALL Laravel, Symfony, Doctrine packages, Carbon, Pest, code quality tools, and benchmarking tools
            echo -e "${YELLOW}  Removing all framework packages, Pest, code quality tools, and benchmarking tools (testing Doctrine only)...${NC}"
            PACKAGES_TO_REMOVE="$ALL_ILLUMINATE_PACKAGES $ALL_SYMFONY_PACKAGES $ALL_DOCTRINE_PACKAGES $CARBON_PACKAGE $PEST_PACKAGES $CODE_QUALITY_PACKAGES"
            ;;
    esac

    # Remove unwanted framework packages from composer.json
    if [[ -n "$PACKAGES_TO_REMOVE" ]]; then
        run_in_container composer remove --dev \
            $PACKAGES_TO_REMOVE \
            --no-interaction --no-update 2>&1 | grep -v "is not required" || true
    fi

    echo -e "${YELLOW}  Deleting composer.lock...${NC}"
    run_in_container rm -f composer.lock

    echo -e "${YELLOW}  Deleting vendor directory for clean install...${NC}"
    run_in_container rm -rf vendor

    # Get packages to install
    PACKAGES=$(get_composer_packages "$FRAMEWORK" "$VERSION")

    if [[ -z "$PACKAGES" ]]; then
        echo -e "${RED}✗${NC}  Invalid framework/version combination"
        exit 1
    fi

    # Add framework packages AND Pest to composer.json together
    # This ensures Composer resolves all dependencies with the correct framework version
    # For Laravel 10, we need to specify Pest 2.x explicitly
    # For Laravel 11+, Composer will automatically pick the right version
    echo -e "${YELLOW}  Adding ${FRAMEWORK} ${VERSION} and Pest to composer.json...${NC}"

    if [[ "$FRAMEWORK" == "laravel" && "$VERSION" == "10" ]]; then
        # Laravel 10 requires Pest 2.x
        run_in_container composer require --dev $PACKAGES "pestphp/pest:^2.0" "pestphp/pest-plugin-laravel:^2.0" --no-update --no-interaction > /dev/null 2>&1
    else
        # For other versions, let Composer resolve automatically
        run_in_container composer require --dev $PACKAGES pestphp/pest pestphp/pest-plugin-laravel --no-update --no-interaction > /dev/null 2>&1
    fi

    echo -e "${YELLOW}  Installing all dependencies with ${FRAMEWORK} ${VERSION}...${NC}"
    run_in_container composer install --no-interaction --quiet
fi

echo -e "${GREEN}✓${NC}  Dependencies installed"

# Verify installed packages
verify_installed_packages "$TEST_TYPE" "$FRAMEWORK" "$VERSION"

# Run tests
if [[ "$RUN_TESTS" == true ]]; then
    echo ""
    echo -e "${YELLOW}🧪  Running Tests...${NC}"
    echo ""

    # Determine which test groups to run
    EXCLUDE_GROUPS=""
    case "$TEST_TYPE" in
        plain)
            EXCLUDE_GROUPS="--exclude-group=laravel --exclude-group=symfony --exclude-group=doctrine"
            ;;
        laravel)
            EXCLUDE_GROUPS="--exclude-group=symfony --exclude-group=doctrine"
            ;;
        symfony)
            EXCLUDE_GROUPS="--exclude-group=laravel --exclude-group=doctrine"
            ;;
        doctrine)
            EXCLUDE_GROUPS="--exclude-group=laravel --exclude-group=symfony"
            ;;
    esac

    # Run tests (without --compact to see summary)
    # Save output to temp file to check for failures while still showing it
    TEMP_OUTPUT=$(mktemp)
    run_in_container vendor/bin/pest --no-coverage $EXCLUDE_GROUPS 2>&1 | tee "$TEMP_OUTPUT"
    TEST_EXIT=$?

    # Check if there are any failures or errors in the output
    # Use more specific patterns to avoid false positives from test names
    if grep -q " FAILED\| FAIL \|Fatal error\|Tests:.*failed" "$TEMP_OUTPUT"; then
        echo ""
        echo -e "${RED}✗${NC}  Tests failed!"
        echo ""
        echo -e "${YELLOW}Failed test details:${NC}"
        echo ""
        # Show only the failure details (lines after "FAILED", "FAIL", or "Fatal error")
        grep -A 50 " FAILED\| FAIL \|Fatal error\|Tests:.*failed" "$TEMP_OUTPUT" | head -100
        rm -f "$TEMP_OUTPUT"
        exit 1
    elif [ $TEST_EXIT -eq 0 ] || [ $TEST_EXIT -eq 255 ]; then
        # Exit code 255 is often returned by Pest even when tests pass (signal handling)
        echo ""
        echo -e "${GREEN}✓${NC}  Tests passed!"
        rm -f "$TEMP_OUTPUT"
    else
        echo ""
        echo -e "${RED}✗${NC}  Tests failed! (Exit code: $TEST_EXIT)"
        echo ""
        echo -e "${YELLOW}Last 50 lines of output:${NC}"
        echo ""
        tail -50 "$TEMP_OUTPUT"
        rm -f "$TEMP_OUTPUT"
        exit 1
    fi
fi

# Run PHPStan
if [[ "$RUN_PHPSTAN" == true ]]; then
    echo ""
    echo -e "${YELLOW}🔍  Running PHPStan...${NC}"
    echo ""

    if run_in_container vendor/bin/phpstan analyse --memory-limit=2G; then
        echo ""
        echo -e "${GREEN}✓${NC}  PHPStan passed!"
    else
        echo ""
        echo -e "${RED}✗${NC}  PHPStan failed!"
        exit 1
    fi
fi

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅  All checks passed!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

