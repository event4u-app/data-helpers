#!/usr/bin/env bash

# Task Output Helper
# Provides consistent, beautiful output for all task commands

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Unicode box drawing characters (more stable than ASCII art)
LINE="━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Function to print a header
task_header() {
    local title="$1"
    echo -e "${BLUE}${LINE}${NC}"
    echo -e "${BLUE}  ${title}${NC}"
    echo -e "${BLUE}${LINE}${NC}"
    echo ""
}

# Function to print a footer
task_footer() {
    echo ""
    echo -e "${BLUE}${LINE}${NC}"
}

# Function to print success message
task_success() {
    local message="$1"
    echo -e "${GREEN}✅  ${message}${NC}"
}

# Function to print error message
task_error() {
    local message="$1"
    echo -e "${RED}❌  ${message}${NC}"
}

# Function to print warning message
task_warning() {
    local message="$1"
    echo -e "${YELLOW}⚠️  ${message}${NC}"
}

# Function to print info message
task_info() {
    local message="$1"
    echo -e "${CYAN}ℹ️  ${message}${NC}"
}

# Function to print step message
task_step() {
    local message="$1"
    echo -e "${YELLOW}→  ${message}${NC}"
}

# Function to run a command with nice output
task_run() {
    local title="$1"
    shift
    local cmd="$@"

    task_header "$title"

    # Run command with live output (no capture)
    set +e
    $cmd
    EXIT_CODE=$?
    set -e

    echo ""
    if [ $EXIT_CODE -eq 0 ]; then
        # Success
        task_success "Command completed successfully"
        task_footer
        return 0
    else
        # Failure
        task_error "Command failed with exit code $EXIT_CODE"
        task_footer
        return $EXIT_CODE
    fi
}

# Function to run tests with nice output
task_test() {
    local title="$1"
    shift
    local cmd="$@"

    task_header "$title"

    # Run command with live output (no capture)
    set +e
    $cmd
    EXIT_CODE=$?
    set -e

    echo ""
    if [ $EXIT_CODE -eq 0 ]; then
        # Success
        task_success "All tests passed!"
        task_footer
        return 0
    else
        # Failure
        task_error "Tests failed!"
        task_footer
        return $EXIT_CODE
    fi
}

# Function to run quality checks with nice output
task_quality() {
    local title="$1"
    shift
    local cmd="$@"

    task_header "$title"

    # Run command with live output (no capture)
    set +e
    $cmd
    EXIT_CODE=$?
    set -e

    echo ""
    if [ $EXIT_CODE -eq 0 ]; then
        # Success
        task_success "Quality check passed!"
        task_footer
        return 0
    else
        # Failure
        task_error "Quality check failed!"
        task_footer
        return $EXIT_CODE
    fi
}

# Note: Functions are defined and can be used after sourcing this script
# No need to export in sh/dash (only works in bash)

