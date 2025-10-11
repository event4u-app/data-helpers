#!/usr/bin/env bash

set -e

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Setting up Laravel E2E environment..."

# Create database directory if it doesn't exist
mkdir -p database

# Always create fresh database
echo "Creating SQLite database..."
touch database/database.sqlite

# Create cache table migration if it doesn't exist
if [ ! -d "database/migrations" ] || [ -z "$(ls -A database/migrations 2>/dev/null)" ]; then
    echo "Creating cache table migration..."
    php artisan cache:table 2>/dev/null || true
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force 2>/dev/null || {
    echo "Warning: Migration failed, but continuing..."
}

echo "Laravel E2E setup complete!"

