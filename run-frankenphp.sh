#!/bin/bash
set -e

echo "🚀 Starting BangronDB Admin with FrankenPHP..."

cd "$(dirname "$0")/backend"

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-interaction
fi

# Create required directories
mkdir -p storage/data storage/logs var

# Start FrankenPHP
echo "🌐 Starting server on http://localhost:8080"
cd ..
/tmp/frankenphp run --config Caddyfile