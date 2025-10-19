#!/usr/bin/env bash

# Test script to verify that package verification works correctly
# This script tests that forbidden packages are detected

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${NC}  ${CYAN}Testing Package Verification${NC}                              ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Test 1: Verify Laravel 9 doesn't have Symfony or Doctrine
echo -e "${YELLOW}Test 1:${NC} Verifying Laravel 9 setup..."
OUTPUT=$(./scripts/test-isolated.sh --laravel 9 --php 8.2 --no-tests 2>&1)

if echo "$OUTPUT" | grep -q "Package verification passed"; then
    echo -e "${GREEN}✓${NC}  Laravel 9: No forbidden packages found"
else
    echo -e "${RED}✗${NC}  Laravel 9: Package verification failed"
    echo "$OUTPUT" | grep -A 10 "Verifying installed packages"
    exit 1
fi

# Test 2: Verify Symfony 7 doesn't have Laravel or Doctrine
echo -e "${YELLOW}Test 2:${NC} Verifying Symfony 7 setup..."
OUTPUT=$(./scripts/test-isolated.sh --symfony 7 --php 8.4 --no-tests 2>&1)

if echo "$OUTPUT" | grep -q "Package verification passed"; then
    echo -e "${GREEN}✓${NC}  Symfony 7: No forbidden packages found"
else
    echo -e "${RED}✗${NC}  Symfony 7: Package verification failed"
    echo "$OUTPUT" | grep -A 10 "Verifying installed packages"
    exit 1
fi

# Test 3: Verify Doctrine 3 doesn't have Laravel or Symfony
echo -e "${YELLOW}Test 3:${NC} Verifying Doctrine 3 setup..."
OUTPUT=$(./scripts/test-isolated.sh --doctrine 3 --php 8.4 --no-tests 2>&1)

if echo "$OUTPUT" | grep -q "Package verification passed"; then
    echo -e "${GREEN}✓${NC}  Doctrine 3: No forbidden packages found"
else
    echo -e "${RED}✗${NC}  Doctrine 3: Package verification failed"
    echo "$OUTPUT" | grep -A 10 "Verifying installed packages"
    exit 1
fi

# Test 4: Verify Plain PHP doesn't have any framework packages
echo -e "${YELLOW}Test 4:${NC} Verifying Plain PHP setup..."
OUTPUT=$(./scripts/test-isolated.sh --plain --php 8.4 --no-tests 2>&1)

if echo "$OUTPUT" | grep -q "Package verification passed"; then
    echo -e "${GREEN}✓${NC}  Plain PHP: No framework packages found"
else
    echo -e "${RED}✗${NC}  Plain PHP: Package verification failed"
    echo "$OUTPUT" | grep -A 10 "Verifying installed packages"
    exit 1
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║${NC}  ${CYAN}✓  All package verification tests passed!${NC}                 ${GREEN}║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

