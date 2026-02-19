#!/bin/bash
# Remote installation script — runs on OVH server
set -euo pipefail

REMOTE_APP="/home/wheelse/2wheels"
REMOTE_WWW="/home/wheelse/www"
ARCHIVE="/home/wheelse/2wheels-deploy.tar.gz"
TMP="/tmp/2wheels-deploy-$$"

echo "▸ Extracting archive..."
rm -rf "$TMP"
mkdir -p "$TMP"
tar xzf "$ARCHIVE" -C "$TMP"

echo "▸ Installing Laravel app..."
rm -rf "$REMOTE_APP"
mv "$TMP/laravel" "$REMOTE_APP"

echo "▸ Installing Next.js static files..."
find "$REMOTE_WWW" -mindepth 1 -maxdepth 1 \
    ! -name '.ovhconfig' \
    -exec rm -rf {} + 2>/dev/null || true

if [ -d "$TMP/out" ]; then
    cp -r "$TMP/out"/* "$REMOTE_WWW/"
    echo "✓ Next.js files copied to www/"
else
    echo "⚠ No out/ directory found in archive"
fi
rm -rf "$TMP"

echo "▸ Installing deploy/index.php and .htaccess..."
cp "$REMOTE_APP/deploy/index.php" "$REMOTE_WWW/index.php"
cp "$REMOTE_APP/deploy/.htaccess" "$REMOTE_WWW/.htaccess"

echo "▸ Installing .env from .env.prd..."
cp "$REMOTE_APP/.env.prd" "$REMOTE_APP/.env"

echo "▸ Creating storage symlink..."
ln -sfn ../2wheels/storage/app/public "$REMOTE_WWW/storage"

echo "▸ Setting permissions..."
chmod -R 775 "$REMOTE_APP/storage" "$REMOTE_APP/bootstrap/cache" 2>/dev/null || true

echo "▸ Creating required storage directories..."
mkdir -p "$REMOTE_APP/storage/framework/cache/data"
mkdir -p "$REMOTE_APP/storage/framework/sessions"
mkdir -p "$REMOTE_APP/storage/framework/views"
mkdir -p "$REMOTE_APP/storage/logs"
mkdir -p "$REMOTE_APP/bootstrap/cache"

# Database import
if [ -f /home/wheelse/dump.sql ]; then
    echo "▸ Importing database..."
    mysql -h wheelse281.mysql.db -u wheelse281 -p'sAMgrhg0iq6e9pj' wheelse281 < /home/wheelse/dump.sql
    echo "✓ Database imported"
    rm -f /home/wheelse/dump.sql
else
    echo "⚠ No dump.sql found, skipping DB import"
fi

# Laravel artisan commands
echo "▸ Running migrations..."
php "$REMOTE_APP/artisan" migrate --force 2>&1 || echo "⚠ Migrations may have failed"

echo "▸ Clearing caches..."
php "$REMOTE_APP/artisan" optimize:clear 2>&1

echo "▸ Caching config & routes..."
php "$REMOTE_APP/artisan" config:cache 2>&1
php "$REMOTE_APP/artisan" route:cache 2>&1
php "$REMOTE_APP/artisan" view:cache 2>&1

# Cleanup
rm -f "$ARCHIVE"
rm -f /home/wheelse/remote-install.sh

echo "✓ DEPLOYMENT COMPLETE"
