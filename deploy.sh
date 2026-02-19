#!/usr/bin/env bash
#
# deploy.sh — Build & deploy 2Wheels to OVH shared hosting
#
# Usage:  ./deploy.sh              (full deploy)
#         ./deploy.sh --skip-build (upload only, reuse previous build)
#         ./deploy.sh --skip-db    (skip DB dump & import)
#
# OVH hosting notes:
#   - SSH commands hang; SCP/SFTP work fine
#   - PHP-FPM runs 8.5 but CLI PHP is 8.2 — artisan must run via web PHP
#   - Remote install uses a PHP trigger script uploaded via SCP, executed via HTTP
#   - mysqldump needs --set-gtid-purged=OFF --no-tablespaces for OVH MySQL
#
set -euo pipefail

# ─── Config ──────────────────────────────────────────────────────
PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
FRONTEND_DIR="$PROJECT_DIR/frontend"
DEPLOY_DIR="$PROJECT_DIR/deploy"
ARCHIVE_NAME="2wheels-deploy.tar.gz"

# Server (from .env.prd)
SSH_HOST="ssh.cluster121.hosting.ovh.net"
SSH_USER="wheelse"
export SSHPASS="JezusBobus377310"
REMOTE_HOME="/home/wheelse"
REMOTE_APP="$REMOTE_HOME/2wheels"
REMOTE_WWW="$REMOTE_HOME/www"
DOMAIN="https://2wheels-rental.pl"
DEPLOY_KEY="2wheels-deploy-2026"

# SCP wrapper (password-based auth via sshpass -e)
do_scp()  { sshpass -e scp -o StrictHostKeyChecking=no "$@"; }

# Database — local
LOCAL_DB_HOST="127.0.0.1"
LOCAL_DB_PORT="3306"
LOCAL_DB_USER="root"
LOCAL_DB_PASS=""
LOCAL_DB_NAME="2wheels_rental"

# Database — remote (from .env.prd)
REMOTE_DB_HOST="wheelse281.mysql.db"
REMOTE_DB_USER="wheelse281"
REMOTE_DB_PASS="sAMgrhg0iq6e9pj"
REMOTE_DB_NAME="wheelse281"

# Frontend build env
BUILD_API_URL="http://localhost:8000/api/2wheels"
BUILD_API_DOMAIN="$DOMAIN"
BUILD_TENANT_ID="019c6cfd-55bb-7082-a6c2-e5c07e61ee07"

# ─── Parse flags ─────────────────────────────────────────────────
SKIP_BUILD=false
SKIP_DB=false
for arg in "$@"; do
    case "$arg" in
        --skip-build) SKIP_BUILD=true ;;
        --skip-db)    SKIP_DB=true ;;
        *)            echo "Unknown flag: $arg"; exit 1 ;;
    esac
done

# ─── Helpers ─────────────────────────────────────────────────────
info()  { printf "\033[1;34m▸ %s\033[0m\n" "$1"; }
ok()    { printf "\033[1;32m✓ %s\033[0m\n" "$1"; }
err()   { printf "\033[1;31m✗ %s\033[0m\n" "$1"; }
die()   { err "$1"; exit 1; }

cleanup() {
    info "Restoring local dev config..."
    restore_frontend_config
    ok "Local config restored"
}

# ─── Frontend config: export mode ────────────────────────────────
set_frontend_export_mode() {
    # next.config.mjs: standalone → export
    sed -i.bak "s/output: 'standalone'/output: 'export'/" "$FRONTEND_DIR/next.config.mjs"

    # Remove force-dynamic / revalidate + comment from regular pages
    for page in \
        "$FRONTEND_DIR/app/page.tsx" \
        "$FRONTEND_DIR/app/polityka-prywatnosci/page.tsx" \
        "$FRONTEND_DIR/app/regulamin/page.tsx"
    do
        sed -i.bak \
            -e '/\/\/ Force dynamic rendering/d' \
            -e "/export const dynamic = 'force-dynamic';/d" \
            -e "/export const revalidate = 0;/d" \
            "$page"
    done

    # Motorcycle detail page: also remove dynamicParams + fix cache
    sed -i.bak \
        -e '/\/\/ Force dynamic rendering/d' \
        -e "/export const dynamic = 'force-dynamic';/d" \
        -e "/export const revalidate = 0;/d" \
        -e "/export const dynamicParams = true;/d" \
        -e "s/cache: 'no-store'/cache: 'force-cache'/g" \
        "$FRONTEND_DIR/app/motocykle/[slug]/page.tsx"

    # lib/api.ts: cache: 'no-store' → cache: 'force-cache' (static export needs cacheable fetches)
    sed -i.bak "s/cache: 'no-store'/cache: 'force-cache'/g" "$FRONTEND_DIR/lib/api.ts"
}

restore_frontend_config() {
    # Restore all .bak files
    for bak in \
        "$FRONTEND_DIR/next.config.mjs.bak" \
        "$FRONTEND_DIR/app/page.tsx.bak" \
        "$FRONTEND_DIR/app/motocykle/[slug]/page.tsx.bak" \
        "$FRONTEND_DIR/app/polityka-prywatnosci/page.tsx.bak" \
        "$FRONTEND_DIR/app/regulamin/page.tsx.bak" \
        "$FRONTEND_DIR/lib/api.ts.bak"
    do
        if [[ -f "$bak" ]]; then
            mv "$bak" "${bak%.bak}"
        fi
    done
}

# ─── Post-build: fix localhost URLs in static HTML ────────────────
fix_localhost_urls() {
    local out_dir="$FRONTEND_DIR/out"
    info "Fixing localhost URLs in static HTML and JS bundles..."
    local count=0
    # Replace http://localhost:8000 with empty string (relative URLs)
    # Cover both HTML files and JS bundles in _next/static/
    while IFS= read -r -d '' file; do
        if grep -q 'http://localhost:8000' "$file"; then
            sed -i '' 's|http://localhost:8000||g' "$file"
            ((count++))
        fi
    done < <(find "$out_dir" \( -name '*.html' -o -name '*.js' \) -print0)
    ok "Fixed localhost URLs in $count files (HTML + JS)"
}

# ═════════════════════════════════════════════════════════════════
# PHASE A: LOCAL BUILD
# ═════════════════════════════════════════════════════════════════

if [[ "$SKIP_BUILD" == false ]]; then
    info "Phase A: Local build"

    # Trap to restore config on failure
    trap cleanup EXIT

    # ── A1: MySQL dump ───────────────────────────────────────────
    if [[ "$SKIP_DB" == false ]]; then
        info "Dumping local MySQL → deploy/dump.sql"
        DUMP_FLAGS="--set-gtid-purged=OFF --no-tablespaces"
        if [[ -n "$LOCAL_DB_PASS" ]]; then
            mysqldump $DUMP_FLAGS -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" \
                -u "$LOCAL_DB_USER" -p"$LOCAL_DB_PASS" \
                "$LOCAL_DB_NAME" > "$DEPLOY_DIR/dump.sql"
        else
            mysqldump $DUMP_FLAGS -h "$LOCAL_DB_HOST" -P "$LOCAL_DB_PORT" \
                -u "$LOCAL_DB_USER" \
                "$LOCAL_DB_NAME" > "$DEPLOY_DIR/dump.sql"
        fi
        ok "DB dump created ($(du -h "$DEPLOY_DIR/dump.sql" | cut -f1))"
    fi

    # ── A2: Prepare Next.js for static export ────────────────────
    info "Switching Next.js to static export mode"
    set_frontend_export_mode
    ok "Frontend patched for export"

    # ── A3: Build Next.js ────────────────────────────────────────
    info "Building Next.js (static export)..."
    cd "$FRONTEND_DIR"

    # Set build-time env vars: API on localhost for data fetching,
    # but output URLs point to production domain
    NEXT_PUBLIC_API_URL="$BUILD_API_URL" \
    NEXT_PUBLIC_API_DOMAIN="$BUILD_API_DOMAIN" \
    NEXT_PUBLIC_TENANT_ID="$BUILD_TENANT_ID" \
    TENANT_ID="$BUILD_TENANT_ID" \
    npx next build

    cd "$PROJECT_DIR"
    ok "Next.js built → frontend/out/"

    # ── A3b: Fix localhost URLs baked into static HTML ────────────
    fix_localhost_urls

    # ── A4: Composer install (production) ────────────────────────
    info "Running composer install --no-dev"
    composer install --no-dev --optimize-autoloader --working-dir="$PROJECT_DIR"
    ok "Composer dependencies optimized"

    # ── A5: Restore frontend config ──────────────────────────────
    info "Restoring frontend dev config"
    restore_frontend_config
    ok "Frontend config restored"

    # Remove the trap since we restored manually
    trap - EXIT

    # ── A6: Create deployment archive ────────────────────────────
    info "Creating deployment archive..."
    cd "$PROJECT_DIR"

    # Stage everything in a temp dir for clean archive
    STAGING=$(mktemp -d)
    trap "rm -rf '$STAGING'" EXIT

    # Copy Laravel app (exclude dev files, frontend build artifacts)
    COPYFILE_DISABLE=1 rsync -a \
        --exclude='node_modules' --exclude='.next' --exclude='frontend/out' \
        --exclude='frontend/node_modules' --exclude='.git' --exclude='deploy' \
        --exclude='.env' --exclude='database.sqlite' \
        --exclude='storage/logs/*.log' \
        --exclude='storage/framework/cache/data/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='bootstrap/cache/config.php' \
        --exclude='.DS_Store' --exclude='._*' \
        . "$STAGING/laravel/"

    # Copy Next.js output (without macOS resource forks)
    rsync -a --exclude='.DS_Store' --exclude='._*' \
        "$FRONTEND_DIR/out/" "$STAGING/out/"

    # Remove Next.js metadata directories that conflict with clean URLs
    # (Next.js creates slug/ dirs alongside slug.html for RSC payloads)
    find "$STAGING/out/motocykle" -mindepth 1 -type d -exec rm -rf {} + 2>/dev/null || true
    # Remove .txt RSC payload files (not needed for static serving)
    find "$STAGING/out" -name '*.txt' -delete 2>/dev/null || true

    # Copy deploy helpers (index.php, .htaccess) and DB dump
    cp -r "$DEPLOY_DIR" "$STAGING/laravel/deploy"

    # Create archive (COPYFILE_DISABLE prevents macOS ._ files in tar)
    COPYFILE_DISABLE=1 tar czf "$DEPLOY_DIR/$ARCHIVE_NAME" -C "$STAGING" .

    rm -rf "$STAGING"
    trap - EXIT

    ok "Archive created: deploy/$ARCHIVE_NAME ($(du -h "$DEPLOY_DIR/$ARCHIVE_NAME" | cut -f1))"

    # ── A7: Restore composer dev dependencies locally ────────────
    info "Restoring composer dev dependencies"
    composer install --working-dir="$PROJECT_DIR"
    ok "Dev dependencies restored"
else
    info "Skipping build (--skip-build)"
    [[ -f "$DEPLOY_DIR/$ARCHIVE_NAME" ]] || die "No archive found at deploy/$ARCHIVE_NAME"
fi

# ═════════════════════════════════════════════════════════════════
# PHASE B: UPLOAD & INSTALL
# ═════════════════════════════════════════════════════════════════
info "Phase B: Upload & install on OVH"

# ── B1: Upload archive ──────────────────────────────────────────
info "Uploading archive to server..."
do_scp "$DEPLOY_DIR/$ARCHIVE_NAME" "$SSH_USER@$SSH_HOST:$REMOTE_HOME/$ARCHIVE_NAME"
ok "Archive uploaded"

# Upload DB dump separately if not skipped
if [[ "$SKIP_DB" == false && -f "$DEPLOY_DIR/dump.sql" ]]; then
    do_scp "$DEPLOY_DIR/dump.sql" "$SSH_USER@$SSH_HOST:$REMOTE_HOME/dump.sql"
    ok "DB dump uploaded"
fi

# ── B2: Upload & run PHP trigger script ──────────────────────────
# NOTE: SSH commands hang on OVH shared hosting.
# Workaround: upload a PHP script to www/, execute it via HTTP.
info "Uploading deploy trigger script..."

# Create the trigger script
TRIGGER_SCRIPT=$(mktemp)
cat > "$TRIGGER_SCRIPT" <<'TRIGGER_PHP'
<?php
/**
 * Remote deployment trigger — extracts archive, installs files, imports DB.
 * Executed via HTTP since SSH commands hang on OVH shared hosting.
 * Self-deletes after execution.
 */
if (($_GET['key'] ?? '') !== 'DEPLOY_KEY_PLACEHOLDER') {
    http_response_code(403);
    die('Forbidden');
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function out($msg) { echo "$msg\n"; flush(); }

$homeDir = '/home/wheelse';
$appDir  = "$homeDir/2wheels";
$wwwDir  = "$homeDir/www";
$archive = "$homeDir/2wheels-deploy.tar.gz";
$dumpFile = "$homeDir/dump.sql";

out("=== DEPLOY TRIGGER ===");
out("PHP " . PHP_VERSION);
out("");

// ── Extract archive ─────────────────────────────────────────────
if (!file_exists($archive)) {
    out("ERROR: Archive not found at $archive");
    exit(1);
}

$tmp = sys_get_temp_dir() . '/2wheels-deploy-' . getmypid();
out("> Extracting archive...");
exec("rm -rf " . escapeshellarg($tmp));
mkdir($tmp, 0755, true);
exec("tar xzf " . escapeshellarg($archive) . " -C " . escapeshellarg($tmp) . " 2>&1", $output, $rc);
if ($rc !== 0) {
    out("ERROR: tar failed: " . implode("\n", $output));
    exit(1);
}
out("OK: Archive extracted");

// ── Install Laravel app ─────────────────────────────────────────
out("> Installing Laravel app...");
if (is_dir($appDir)) {
    // Preserve storage/app/public (uploaded images)
    $preserveStorage = is_dir("$appDir/storage/app/public");
    if ($preserveStorage) {
        exec("mv " . escapeshellarg("$appDir/storage/app/public") . " " . escapeshellarg("$tmp/saved-storage"));
    }
    exec("rm -rf " . escapeshellarg($appDir));
}
rename("$tmp/laravel", $appDir);
// Restore preserved storage
if (isset($preserveStorage) && $preserveStorage && is_dir("$tmp/saved-storage")) {
    exec("rm -rf " . escapeshellarg("$appDir/storage/app/public"));
    exec("mv " . escapeshellarg("$tmp/saved-storage") . " " . escapeshellarg("$appDir/storage/app/public"));
    out("OK: Preserved uploaded images in storage/app/public");
}
out("OK: Laravel app installed");

// ── Install Next.js static files ────────────────────────────────
out("> Installing Next.js static files...");
// Clean www/ but keep .htaccess, index.php, .ovhconfig, storage symlink, this script
$keepFiles = ['.', '..', '.ovhconfig', 'trigger-deploy.php'];
foreach (scandir($wwwDir) as $item) {
    if (in_array($item, $keepFiles)) continue;
    $path = "$wwwDir/$item";
    if (is_link($path)) unlink($path);
    elseif (is_dir($path)) exec("rm -rf " . escapeshellarg($path));
    else unlink($path);
}

if (is_dir("$tmp/out")) {
    exec("cp -r " . escapeshellarg("$tmp/out") . "/* " . escapeshellarg($wwwDir) . "/ 2>&1", $output, $rc);
    out("OK: Next.js files copied to www/");
} else {
    out("WARN: No out/ directory in archive");
}
exec("rm -rf " . escapeshellarg($tmp));

// ── Copy Laravel public assets (Filament CSS/JS, Vite build) ────
out("> Copying Laravel public assets to www/...");
foreach (['css', 'js', 'build', 'vendor'] as $assetDir) {
    $src = "$appDir/public/$assetDir";
    $dst = "$wwwDir/$assetDir";
    if (is_dir($src)) {
        exec("cp -r " . escapeshellarg($src) . " " . escapeshellarg($dst) . " 2>&1");
        out("  copied: $assetDir/");
    }
}
out("OK: Laravel public assets installed");

// ── Publish Livewire static assets (bypass PHP for JS delivery) ──
// On OVH shared hosting, serving livewire.min.js through PHP causes
// NS_ERROR_NET_PARTIAL_TRANSFER. Serve as static files via Apache instead.
out("> Publishing Livewire static assets...");
$lwSrc = "$appDir/vendor/livewire/livewire/dist";
$lwDst = "$wwwDir/livewire";
if (!is_dir($lwDst)) mkdir($lwDst, 0755, true);
if (is_dir($lwSrc)) {
    foreach (['livewire.min.js', 'manifest.json'] as $f) {
        if (file_exists("$lwSrc/$f")) {
            copy("$lwSrc/$f", "$lwDst/$f");
            out("  published: livewire/$f (" . filesize("$lwDst/$f") . " bytes)");
        }
    }
    out("OK: Livewire assets published as static files");
} else {
    out("WARN: Livewire dist not found at $lwSrc");
}

// ── Install deploy files ────────────────────────────────────────
out("> Installing index.php and .htaccess...");
copy("$appDir/deploy/index.php", "$wwwDir/index.php");
copy("$appDir/deploy/.htaccess", "$wwwDir/.htaccess");
out("OK: index.php and .htaccess installed");

// ── Install .env ────────────────────────────────────────────────
out("> Installing .env...");
copy("$appDir/.env.prd", "$appDir/.env");
out("OK: .env installed from .env.prd");

// ── Storage symlink ─────────────────────────────────────────────
out("> Creating storage symlink...");
$storageLink = "$wwwDir/storage";
if (is_link($storageLink)) unlink($storageLink);
symlink("../2wheels/storage/app/public", $storageLink);
out("OK: storage -> ../2wheels/storage/app/public");

// ── Permissions ─────────────────────────────────────────────────
out("> Setting permissions...");
exec("chmod -R 775 " . escapeshellarg("$appDir/storage") . " " . escapeshellarg("$appDir/bootstrap/cache") . " 2>&1");
// Create required directories
foreach (['storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache'] as $dir) {
    $fullDir = "$appDir/$dir";
    if (!is_dir($fullDir)) mkdir($fullDir, 0775, true);
}
out("OK: Permissions set");

// ── Database import ─────────────────────────────────────────────
if (file_exists($dumpFile)) {
    out("> Importing database...");
    $dbHost = 'DB_HOST_PLACEHOLDER';
    $dbUser = 'DB_USER_PLACEHOLDER';
    $dbPass = 'DB_PASS_PLACEHOLDER';
    $dbName = 'DB_NAME_PLACEHOLDER';
    $cmd = "mysql -h " . escapeshellarg($dbHost) . " -u " . escapeshellarg($dbUser)
         . " -p" . escapeshellarg($dbPass) . " " . escapeshellarg($dbName)
         . " < " . escapeshellarg($dumpFile) . " 2>&1";
    exec($cmd, $output, $rc);
    if ($rc === 0) {
        out("OK: Database imported");
        unlink($dumpFile);
    } else {
        out("ERROR: DB import failed: " . implode("\n", $output));
    }
} else {
    out("SKIP: No dump.sql found");
}

// ── Laravel artisan commands (via web PHP, not CLI) ─────────────
out("> Running artisan commands...");

// OVH CLI PHP is 8.2 but Laravel 12 needs 8.4+. Web PHP-FPM is 8.5.
// Strategy: try CLI first, then bootstrap Artisan in current web PHP process.
$artisanCommands = ['migrate --force', 'optimize:clear'];

foreach ($artisanCommands as $artisanCmd) {
    out("  artisan $artisanCmd...");
    $fullCmd = "cd " . escapeshellarg($appDir) . " && php artisan $artisanCmd 2>&1";
    $cmdOutput = [];
    exec($fullCmd, $cmdOutput, $cmdRc);
    if ($cmdRc === 0) {
        out("  OK: $artisanCmd");
    } else {
        out("  WARN: CLI failed (PHP " . PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . " via exec), trying web bootstrap...");
        try {
            require_once "$appDir/vendor/autoload.php";
            $laravelApp = require "$appDir/bootstrap/app.php";
            $laravelApp->usePublicPath($wwwDir);
            $kernel = $laravelApp->make(\Illuminate\Contracts\Console\Kernel::class);
            $kernel->bootstrap();
            $status = \Illuminate\Support\Facades\Artisan::call($artisanCmd);
            $artOutput = \Illuminate\Support\Facades\Artisan::output();
            out("  " . trim($artOutput));
            out("  OK (web PHP): $artisanCmd (exit: $status)");
        } catch (\Throwable $e) {
            out("  ERROR: " . $e->getMessage());
        }
    }
}

// NOTE: We intentionally skip config:cache. Without it, Laravel reads
// .env directly on each request. This avoids issues with cached config
// generated by incompatible PHP versions (CLI 8.2 vs FPM 8.5).

// ── Cleanup ─────────────────────────────────────────────────────
out("> Cleaning up...");
@unlink($archive);
@unlink("$homeDir/dump.sql");
out("OK: Archive cleaned up");

// Self-delete
@unlink(__FILE__);

out("\n=== DEPLOY COMPLETE ===");
TRIGGER_PHP

# Replace placeholders with actual values
sed -i '' "s|DEPLOY_KEY_PLACEHOLDER|$DEPLOY_KEY|g" "$TRIGGER_SCRIPT"
sed -i '' "s|DB_HOST_PLACEHOLDER|$REMOTE_DB_HOST|g" "$TRIGGER_SCRIPT"
sed -i '' "s|DB_USER_PLACEHOLDER|$REMOTE_DB_USER|g" "$TRIGGER_SCRIPT"
sed -i '' "s|DB_PASS_PLACEHOLDER|$REMOTE_DB_PASS|g" "$TRIGGER_SCRIPT"
sed -i '' "s|DB_NAME_PLACEHOLDER|$REMOTE_DB_NAME|g" "$TRIGGER_SCRIPT"

do_scp "$TRIGGER_SCRIPT" "$SSH_USER@$SSH_HOST:$REMOTE_WWW/trigger-deploy.php"
rm -f "$TRIGGER_SCRIPT"
ok "Trigger script uploaded"

# ── B3: Execute trigger via HTTP ─────────────────────────────────
info "Running deployment on server via HTTP trigger..."
TRIGGER_URL="$DOMAIN/trigger-deploy.php?key=$DEPLOY_KEY"
TRIGGER_OUTPUT=$(curl -sSk --max-time 300 "$TRIGGER_URL" 2>&1)
echo "$TRIGGER_OUTPUT"

if echo "$TRIGGER_OUTPUT" | grep -q "DEPLOY COMPLETE"; then
    ok "Remote deployment complete"
else
    err "Deployment may have issues — check output above"
fi

# ═════════════════════════════════════════════════════════════════
# PHASE C: VERIFICATION
# ═════════════════════════════════════════════════════════════════
info "Phase C: Verification"

PASS=0
FAIL=0

check_url() {
    local label="$1" url="$2" expect="$3"
    local code
    code=$(curl -sSk -o /dev/null -w "%{http_code}" --max-time 10 "$url" 2>/dev/null || echo "000")
    if [[ "$code" == "$expect" ]]; then
        ok "$label → $code"
        ((PASS++))
    else
        err "$label → $code (expected $expect)"
        ((FAIL++))
    fi
}

check_url "Homepage"       "$DOMAIN"                                  "200"
check_url "Admin panel"    "$DOMAIN/admin"                            "200"
check_url "API endpoint"   "$DOMAIN/api/2wheels/site-setting"         "200"
check_url "Storage file"   "$DOMAIN/storage/2wheels/logo.jpg"         "200"
check_url "Motorcycle"     "$DOMAIN/motocykle/bmw-m1000r-competition" "200"
check_url "Regulamin"      "$DOMAIN/regulamin"                        "200"

# Check that images use relative URLs (not localhost)
info "Checking image URLs..."
if curl -sSk --max-time 10 "$DOMAIN/motocykle/bmw-m1000r-competition" | grep -q 'localhost:8000'; then
    err "Found localhost URLs in HTML — images will be broken!"
    ((FAIL++))
else
    ok "No localhost URLs found in HTML"
    ((PASS++))
fi

echo ""
if [[ "$FAIL" -eq 0 ]]; then
    ok "All $PASS checks passed! Deployment complete."
else
    err "$FAIL check(s) failed, $PASS passed."
fi
