#!/bin/bash
set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DEPLOY_DIR"

echo "==> Pulling latest code..."
git pull origin master

echo "==> Installing dependencies..."
composer install --no-dev --optimize-autoloader --quiet

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Clearing & rebuilding cache..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage..."
php artisan storage:link 2>/dev/null || true

echo "✓ Deploy complete."
