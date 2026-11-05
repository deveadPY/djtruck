#!/bin/bash
set -e

DEPLOY_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DEPLOY_DIR"

echo "==> Pulling latest code..."
git pull origin main

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

echo "==> Re-protecting sensitive files..."
chmod 600 .env

echo "==> Security advisories (informativo, no bloquea el deploy):"
composer audit --no-dev --format=summary || true

echo "✓ Deploy complete."
