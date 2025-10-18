#!/usr/bin/env bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Icons
ICON_SUCCESS="✅"
ICON_ERROR="❌"
ICON_INFO="ℹ️"
ICON_WARNING="⚠️"
ICON_PACKAGE="📦"
ICON_TEST="🧪"
ICON_RESTORE="🔄"

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Test ohne Framework-Dependencies (Doctrine, Symfony, Laravel)${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
echo ""

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

cd "$PROJECT_ROOT"

# Backup file
BACKUP_FILE="$PROJECT_ROOT/.composer.json.backup"
REMOVED_PACKAGES_FILE="$PROJECT_ROOT/.removed-packages.txt"

# Function to print section header
print_section() {
    echo ""
    echo -e "${BLUE}───────────────────────────────────────────────────────────────${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}───────────────────────────────────────────────────────────────${NC}"
    echo ""
}

# Function to print success
print_success() {
    echo -e "${GREEN}${ICON_SUCCESS}  $1${NC}"
}

# Function to print error
print_error() {
    echo -e "${RED}${ICON_ERROR}  $1${NC}"
}

# Function to print info
print_info() {
    echo -e "${BLUE}${ICON_INFO}  $1${NC}"
}

# Function to print warning
print_warning() {
    echo -e "${YELLOW}${ICON_WARNING}  $1${NC}"
}

# Cleanup function
cleanup() {
    if [ -f "$BACKUP_FILE" ]; then
        print_info "Cleanup: Entferne Backup-Datei..."
        rm -f "$BACKUP_FILE"
    fi
    if [ -f "$REMOVED_PACKAGES_FILE" ]; then
        rm -f "$REMOVED_PACKAGES_FILE"
    fi
}

# Trap to ensure cleanup on exit
trap cleanup EXIT

# Step 1: Backup composer.json
print_section "1. Backup composer.json"
cp composer.json "$BACKUP_FILE"
print_success "Backup erstellt: $BACKUP_FILE"

# Step 2: Identify and save packages to remove
print_section "2. Identifiziere Framework-Packages"

DOCTRINE_PACKAGES=$(composer show --name-only | grep -E '^doctrine/' || true)
SYMFONY_PACKAGES=$(composer show --name-only | grep -E '^symfony/' || true)
LARAVEL_PACKAGES=$(composer show --name-only | grep -E '^illuminate/' || true)

# Save all packages to file
{
    echo "$DOCTRINE_PACKAGES"
    echo "$SYMFONY_PACKAGES"
    echo "$LARAVEL_PACKAGES"
} | grep -v '^$' > "$REMOVED_PACKAGES_FILE"

TOTAL_PACKAGES=$(wc -l < "$REMOVED_PACKAGES_FILE" | tr -d ' ')

print_info "Gefundene Packages:"
echo ""
if [ -n "$DOCTRINE_PACKAGES" ]; then
    echo -e "${YELLOW}${ICON_PACKAGE}  Doctrine Packages:${NC}"
    echo "$DOCTRINE_PACKAGES" | sed 's/^/    /'
    echo ""
fi
if [ -n "$SYMFONY_PACKAGES" ]; then
    echo -e "${YELLOW}${ICON_PACKAGE}  Symfony Packages:${NC}"
    echo "$SYMFONY_PACKAGES" | sed 's/^/    /'
    echo ""
fi
if [ -n "$LARAVEL_PACKAGES" ]; then
    echo -e "${YELLOW}${ICON_PACKAGE}  Laravel Packages:${NC}"
    echo "$LARAVEL_PACKAGES" | sed 's/^/    /'
    echo ""
fi

print_info "Gesamt: $TOTAL_PACKAGES Packages werden entfernt"

# Step 3: Remove packages
print_section "3. Entferne Framework-Packages"

if [ "$TOTAL_PACKAGES" -eq 0 ]; then
    print_warning "Keine Packages zum Entfernen gefunden!"
else
    print_info "Entferne $TOTAL_PACKAGES Packages..."

    # Remove packages one by one to avoid issues
    while IFS= read -r package; do
        if [ -n "$package" ]; then
            echo -n "  Entferne $package... "
            if composer remove --dev "$package" --no-update --quiet 2>/dev/null; then
                echo -e "${GREEN}OK${NC}"
            else
                echo -e "${YELLOW}Übersprungen${NC}"
            fi
        fi
    done < "$REMOVED_PACKAGES_FILE"

    print_info "Aktualisiere Composer..."
    composer update --quiet --no-interaction

    print_success "Alle Packages entfernt!"
fi

# Step 4: Run tests without framework dependencies
print_section "4. Führe Tests OHNE Framework-Dependencies aus"

print_info "Starte Tests..."
echo ""

if vendor/bin/pest --compact --no-coverage --exclude-group=performance 2>&1; then
    echo ""
    print_success "Tests OHNE Framework-Dependencies bestanden!"
    TESTS_WITHOUT_DEPS_PASSED=true
else
    echo ""
    print_error "Tests OHNE Framework-Dependencies fehlgeschlagen!"
    TESTS_WITHOUT_DEPS_PASSED=false
fi

# Step 5: Restore composer.json
print_section "5. Stelle composer.json wieder her"

cp "$BACKUP_FILE" composer.json
print_success "composer.json wiederhergestellt"

# Step 6: Reinstall packages
print_section "6. Installiere Framework-Packages wieder"

print_info "Installiere Packages..."
composer install --quiet --no-interaction

print_success "Alle Packages wieder installiert!"

# Step 7: Run tests with framework dependencies
print_section "7. Führe Tests MIT Framework-Dependencies aus"

print_info "Starte Tests..."
echo ""

if vendor/bin/pest --compact --no-coverage --exclude-group=performance 2>&1; then
    echo ""
    print_success "Tests MIT Framework-Dependencies bestanden!"
    TESTS_WITH_DEPS_PASSED=true
else
    echo ""
    print_error "Tests MIT Framework-Dependencies fehlgeschlagen!"
    TESTS_WITH_DEPS_PASSED=false
fi

# Final summary
print_section "Zusammenfassung"

echo ""
echo -e "${BLUE}Test-Ergebnisse:${NC}"
echo ""

if [ "$TESTS_WITHOUT_DEPS_PASSED" = true ]; then
    print_success "Tests OHNE Framework-Dependencies: BESTANDEN"
else
    print_error "Tests OHNE Framework-Dependencies: FEHLGESCHLAGEN"
fi

if [ "$TESTS_WITH_DEPS_PASSED" = true ]; then
    print_success "Tests MIT Framework-Dependencies: BESTANDEN"
else
    print_error "Tests MIT Framework-Dependencies: FEHLGESCHLAGEN"
fi

echo ""
echo -e "${BLUE}Entfernte Packages: ${YELLOW}$TOTAL_PACKAGES${NC}"
echo ""

# Exit with appropriate code
if [ "$TESTS_WITHOUT_DEPS_PASSED" = true ] && [ "$TESTS_WITH_DEPS_PASSED" = true ]; then
    echo -e "${GREEN}${ICON_SUCCESS}  Alle Tests bestanden! Das Package ist framework-agnostic! 🎉${NC}"
    echo ""
    exit 0
else
    echo -e "${RED}${ICON_ERROR}  Einige Tests sind fehlgeschlagen!${NC}"
    echo ""
    exit 1
fi

