#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
FRAMEWORK=""
VERSION=""
RUN_TESTS=true
RUN_PHPSTAN=false

# Function to display usage
usage() {
    echo -e "${BLUE}Usage:${NC} $0 [OPTIONS]"
    echo ""
    echo "Test the package with specific framework versions."
    echo ""
    echo -e "${YELLOW}Options:${NC}"
    echo "  -l, --laravel VERSION    Test with Laravel version (9, 10, or 11)"
    echo "  -s, --symfony VERSION    Test with Symfony version (6 or 7)"
    echo "  -d, --doctrine VERSION   Test with Doctrine ORM version (2 or 3)"
    echo "  -p, --phpstan            Run PHPStan after tests"
    echo "  --no-tests               Skip running tests"
    echo "  -h, --help               Display this help message"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    echo "  $0 -l 9                  # Test with Laravel 9 (auto-resolves dependencies)"
    echo "  $0 -l 11 -p              # Test with Laravel 11 and run PHPStan"
    echo "  $0 -s 6                  # Test with Symfony 6"
    echo "  $0 -d 3                  # Test with Doctrine ORM 3"
    echo ""
    exit 0
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
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

# Function to validate framework version
validate_version() {
    local framework=$1
    local version=$2

    case $framework in
        laravel)
            [[ "$version" =~ ^(9|10|11)$ ]] || {
                echo -e "${RED}Error:${NC} Invalid Laravel version. Must be 9, 10, or 11."
                exit 1
            }
            ;;
        symfony)
            [[ "$version" =~ ^(6|7)$ ]] || {
                echo -e "${RED}Error:${NC} Invalid Symfony version. Must be 6 or 7."
                exit 1
            }
            ;;
        doctrine)
            [[ "$version" =~ ^(2|3)$ ]] || {
                echo -e "${RED}Error:${NC} Invalid Doctrine version. Must be 2 or 3."
                exit 1
            }
            ;;
    esac
}

# Validate framework and version
if [[ -z "$FRAMEWORK" ]]; then
    echo -e "${RED}Error:${NC} No framework specified. Use -l, -s, or -d."
    exit 1
fi

validate_version "$FRAMEWORK" "$VERSION"

# Backup original files
BACKUP_DIR=$(mktemp -d)
trap "cp \"$BACKUP_DIR/composer.json\" composer.json 2>/dev/null || true; cp \"$BACKUP_DIR/composer.lock\" composer.lock 2>/dev/null || true; rm -rf \"$BACKUP_DIR\"" EXIT

# Display configuration
echo -e "${BLUE}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}  Testing event4u/data-helpers with specific versions       ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}Framework:${NC} $FRAMEWORK $VERSION"
echo -e "${YELLOW}Run Tests:${NC} $RUN_TESTS"
echo -e "${YELLOW}Run PHPStan:${NC} $RUN_PHPSTAN"
echo ""

# Backup composer files
echo -e "${BLUE}→${NC} Backing up composer files..."
cp composer.json "$BACKUP_DIR/composer.json"
if [[ -f composer.lock ]]; then
    cp composer.lock "$BACKUP_DIR/composer.lock"
fi

# Update dependencies
echo -e "${BLUE}→${NC} Updating dependencies..."

# Function to remove vendor directory with retry logic
remove_vendor() {
    [[ ! -d vendor ]] && return 0

    # Try normal removal first
    rm -rf vendor 2>/dev/null && return 0

    # Retry with force permissions
    echo -e "${YELLOW}  Retrying vendor removal with force...${NC}"
    chmod -R u+w vendor 2>/dev/null || true
    rm -rf vendor 2>/dev/null && return 0

    # Alternative method: remove files first, then directories
    echo -e "${YELLOW}  Using alternative removal method...${NC}"
    find vendor -type f -delete 2>/dev/null || true
    find vendor -type d -empty -delete 2>/dev/null || true
    rm -rf vendor 2>/dev/null || true
}

# Remove composer.lock and vendor to force fresh dependency resolution
if [[ -f composer.lock ]]; then
    echo -e "${YELLOW}  Removing composer.lock and vendor for fresh dependency resolution...${NC}"
    rm -f composer.lock
    remove_vendor
fi

# Function to get required packages for framework
get_required_packages() {
    local framework=$1
    local version=$2

    case $framework in
        laravel)
            echo "illuminate/support:^${version}.0 illuminate/database:^${version}.0 illuminate/http:^${version}.0"
            ;;
        symfony)
            echo "symfony/http-kernel:^${version}.0 symfony/http-foundation:^${version}.0"
            ;;
        doctrine)
            echo "doctrine/orm:^${version}.0 doctrine/collections:^${version}.0"
            ;;
    esac
}

# Get required packages
REQUIRE_PACKAGES=$(get_required_packages "$FRAMEWORK" "$VERSION")
read -ra REQUIRE_COMMANDS <<< "$REQUIRE_PACKAGES"

if [[ ${#REQUIRE_COMMANDS[@]} -gt 0 ]]; then
    echo -e "${YELLOW}  Installing ${FRAMEWORK} ${VERSION}...${NC}"
    COMPOSER_OUTPUT=$(composer require --dev --prefer-dist --no-interaction -W "${REQUIRE_COMMANDS[@]}" 2>&1)
else
    echo -e "${YELLOW}  Running composer update with dependency resolution (-W)...${NC}"
    COMPOSER_OUTPUT=$(composer update php --prefer-dist --no-interaction -W 2>&1)
fi

COMPOSER_EXIT_CODE=$?

# Check for errors
if [[ $COMPOSER_EXIT_CODE -ne 0 ]]; then
    echo -e "${YELLOW}  Retry composer update with dependency resolution (-W)...${NC}"
    COMPOSER_OUTPUT=$(composer update php --prefer-dist --no-interaction -W 2>&1)
fi

# Check for errors
if [[ $COMPOSER_EXIT_CODE -ne 0 ]]; then
    echo "$COMPOSER_OUTPUT"

    # Check if it's a Docker/filesystem issue
    if echo "$COMPOSER_OUTPUT" | grep -q "Could not delete\|can't remove\|Directory not empty"; then
        echo ""
        echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${RED}║${NC}  ${YELLOW}⚠️  Docker Container Filesystem Issue Detected${NC}           ${RED}║${NC}"
        echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
        echo ""
        echo -e "${YELLOW}This script may not work correctly inside Docker containers${NC}"
        echo -e "${YELLOW}due to filesystem permission issues with mounted volumes.${NC}"
        echo ""
        echo -e "${BLUE}Please try running this script locally (outside the container):${NC}"
        echo -e "  ${GREEN}cd /path/to/data-helpers${NC}"
        echo -e "  ${GREEN}composer test:${FRAMEWORK}${VERSION}${NC}"
        echo ""
    fi

    exit $COMPOSER_EXIT_CODE
fi

echo "$COMPOSER_OUTPUT"

echo -e "${GREEN}✓${NC} Dependencies updated"
echo ""

# Function to show installed package versions
show_installed_versions() {
    local framework=$1
    local packages

    case $framework in
        laravel)
            packages="illuminate/database illuminate/http illuminate/support"
            ;;
        symfony)
            packages="symfony/http-kernel symfony/http-foundation"
            ;;
        doctrine)
            packages="doctrine/orm doctrine/collections"
            ;;
    esac

    composer show $packages | grep -E "^(name|versions)" | awk '{if(NR%2==1) printf "  • %-35s ", $3; else print $3, $4}'
}

# Show installed versions
echo -e "${BLUE}→${NC} Installed versions:"
show_installed_versions "$FRAMEWORK"
echo ""

# Function to run a command with status output
run_check() {
    local name=$1
    local command=$2

    echo -e "${BLUE}→${NC} Running ${name}..."
    echo ""

    if $command; then
        echo ""
        echo -e "${GREEN}✓${NC} ${name} passed!"
        return 0
    else
        echo ""
        echo -e "${RED}✗${NC} ${name} failed!"
        exit 1
    fi
}

# Run tests
[[ "$RUN_TESTS" == true ]] && run_check "tests" "composer test"

# Run PHPStan
if [[ "$RUN_PHPSTAN" == true ]]; then
    echo ""
    run_check "PHPStan" "composer phpstan"
fi

# Success message
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  All checks passed successfully! 🎉                        ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"

# Cleanup is handled by trap
echo ""
echo -e "${BLUE}→${NC} Restoring original composer files..."
echo -e "${GREEN}✓${NC} Cleanup complete"

