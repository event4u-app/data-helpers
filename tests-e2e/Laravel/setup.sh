#!/usr/bin/env bash

set -e

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "Setting up Laravel E2E environment..."

# Create database directory if it doesn't exist
mkdir -p database

# Remove old database and create fresh one
echo "Creating SQLite database..."
rm -f database/database.sqlite
touch database/database.sqlite

# Run migrations
echo "Running migrations..."
php artisan migrate --force

echo "Laravel E2E setup complete!"

