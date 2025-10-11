#!/usr/bin/env bash

set -e

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Create database directory if it doesn't exist
mkdir -p database

# Create SQLite database if it doesn't exist
if [ ! -f "database/database.sqlite" ]; then
    echo "Creating SQLite database..."
    touch database/database.sqlite
fi

# Run migrations if cache table doesn't exist
if ! php artisan migrate:status --quiet 2>/dev/null | grep -q "create_cache_table"; then
    echo "Setting up cache table..."
    php artisan cache:table --quiet 2>/dev/null || true
    php artisan migrate --force --quiet
fi

echo "Laravel E2E setup complete!"

