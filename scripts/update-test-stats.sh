#!/usr/bin/env bash

# Update test statistics in README and documentation
# Reads test output from stdin and updates files with rounded statistics

set -euo pipefail

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅  $1${NC}"
}

# Function to round down to nearest hundred
round_down_hundred() {
    local number=$1
    echo $((number / 100 * 100))
}

# Function to extract test statistics from Pest output
extract_test_stats() {
    local output="$1"
    local tests=0
    local assertions=0
    
    # Try to extract from "Tests: X passed (Y assertions)" format
    if echo "$output" | grep -q "Tests:.*passed.*assertions"; then
        # Extract total passed tests
        tests=$(echo "$output" | grep -oE "Tests:.*passed" | grep -oE "[0-9]+ passed" | grep -oE "[0-9]+" | head -1)
        
        # Extract assertions
        assertions=$(echo "$output" | grep -oE "\([0-9]+ assertions\)" | grep -oE "[0-9]+" | head -1)
    fi
    
    # If not found, try alternative format
    if [ "$tests" -eq 0 ]; then
        tests=$(echo "$output" | grep -oE "[0-9]+ tests" | grep -oE "[0-9]+" | head -1 || echo "0")
    fi
    
    if [ "$assertions" -eq 0 ]; then
        assertions=$(echo "$output" | grep -oE "[0-9]+ assertions" | grep -oE "[0-9]+" | head -1 || echo "0")
    fi
    
    echo "$tests $assertions"
}

# Main function
main() {
    local test_output=""
    
    # Read from stdin
    if [ ! -t 0 ]; then
        test_output=$(cat)
    else
        print_info "No test output provided via stdin"
        exit 1
    fi
    
    # Extract stats
    read -r tests assertions <<< "$(extract_test_stats "$test_output")"
    
    if [ "$tests" -eq 0 ] || [ "$assertions" -eq 0 ]; then
        print_info "Could not extract test statistics from output"
        exit 0
    fi
    
    # Round down to nearest hundred
    local tests_rounded=$(round_down_hundred "$tests")
    local assertions_rounded=$(round_down_hundred "$assertions")
    
    print_info "Found $tests tests, $assertions assertions"
    print_info "Rounded: ${tests_rounded}+ tests, ${assertions_rounded}+ assertions"
    
    # Update README.md
    if [ -f "README.md" ]; then
        perl -i -pe "s/\d+\+?\s+tests/${tests_rounded}+ tests/g" README.md
        print_success "Updated README.md"
    fi
    
    # Update starlight/src/content/docs/index.mdx
    if [ -f "starlight/src/content/docs/index.mdx" ]; then
        perl -i -pe "s/\d+\+?\s+tests/${tests_rounded}+ tests/g" starlight/src/content/docs/index.mdx
        perl -i -pe "s/\d+\+?\s+assertions/${assertions_rounded}+ assertions/g" starlight/src/content/docs/index.mdx
        print_success "Updated starlight/src/content/docs/index.mdx"
    fi
}

# Run main function
main "$@"

