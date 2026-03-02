<?php
/**
 * Remote deployment trigger — upload to www/, run via HTTP, then delete.
 *
 * Usage: curl "https://2wheels-rental.pl/remote-deploy.php?key=2wheels-deploy-2026"
 */

// Security: simple shared key
$expectedKey = '2wheels-deploy-2026';
if (($_GET['key'] ?? '') !== $expectedKey) {
    http_response_code(403);
    die('Forbidden');
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

function out($msg) {
    echo $msg . "\n";
    flush();
    if (ob_get_level()) ob_flush();
}

$homeDir = '/home/wheelse';
$appDir = "$homeDir/2wheels";
$wwwDir = "$homeDir/www";
$archive = "$homeDir/2wheels-deploy.tar.gz";
$tmpDir = "$homeDir/tmp-deploy-" . getmypid();

// ── Step 1: Extract archive ─────────────────────────────────────
if (!file_exists($archive)) {
    out("ERROR: Archive not found at $archive");
    exit(1);
}

out("▸ Extracting archive...");
@mkdir($tmpDir, 0755, true);
exec("tar xzf " . escapeshellarg($archive) . " -C " . escapeshellarg($tmpDir) . " 2>&1", $output, $ret);
out(implode("\n", $output));
if ($ret !== 0) {
    out("ERROR: Failed to extract archive (exit $ret)");
    exit(1);
}

// ── Step 2: Install Laravel app ─────────────────────────────────
out("▸ Installing Laravel app...");
exec("rm -rf " . escapeshellarg($appDir) . " 2>&1", $output);
exec("mv " . escapeshellarg("$tmpDir/laravel") . " " . escapeshellarg($appDir) . " 2>&1", $output, $ret);
if ($ret !== 0) {
    out("ERROR: Failed to move Laravel app (exit $ret)");
    out(implode("\n", $output));
    exit(1);
}
out("✓ Laravel app installed");

// ── Step 3: Install Next.js static files ────────────────────────
out("▸ Installing Next.js static files...");

// Save this script and .ovhconfig before cleaning www/
$selfPath = __FILE__;
$selfContent = file_get_contents($selfPath);
$ovhConfig = file_exists("$wwwDir/.ovhconfig") ? file_get_contents("$wwwDir/.ovhconfig") : null;

// Clean www/
$items = scandir($wwwDir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = "$wwwDir/$item";
    if (is_dir($path)) {
        exec("rm -rf " . escapeshellarg($path) . " 2>&1");
    } else {
        unlink($path);
    }
}

// Copy Next.js output
if (is_dir("$tmpDir/out")) {
    exec("cp -r " . escapeshellarg("$tmpDir/out") . "/* " . escapeshellarg($wwwDir) . "/ 2>&1", $output, $ret);
    out("✓ Next.js files copied to www/");
} else {
    out("⚠ No out/ directory found in archive");
}

// Restore .ovhconfig
if ($ovhConfig !== null) {
    file_put_contents("$wwwDir/.ovhconfig", $ovhConfig);
}

// ── Step 4: Install deploy helpers ──────────────────────────────
out("▸ Installing deploy/index.php and .htaccess...");
copy("$appDir/deploy/index.php", "$wwwDir/index.php");
copy("$appDir/deploy/.htaccess", "$wwwDir/.htaccess");

// Install root .htaccess (OVH docroot is /home/wheelse/, rewrites to www/)
if (file_exists("$appDir/deploy/root-htaccess")) {
    copy("$appDir/deploy/root-htaccess", "$homeDir/.htaccess");
    out("✓ Root .htaccess installed (rewrites to www/)");
}

// ── Step 5: Install .env ────────────────────────────────────────
out("▸ Installing .env from .env.prd...");
copy("$appDir/.env.prd", "$appDir/.env");

// ── Step 6: Storage symlink ─────────────────────────────────────
out("▸ Creating storage symlink...");
$symlinkTarget = "../2wheels/storage/app/public";
$symlinkPath = "$wwwDir/storage";
if (is_link($symlinkPath)) unlink($symlinkPath);
if (file_exists($symlinkPath)) exec("rm -rf " . escapeshellarg($symlinkPath));
symlink($symlinkTarget, $symlinkPath);

// ── Step 7: Permissions ─────────────────────────────────────────
out("▸ Setting permissions...");
exec("chmod -R 775 " . escapeshellarg("$appDir/storage") . " " . escapeshellarg("$appDir/bootstrap/cache") . " 2>&1");

// Create required directories
$dirs = [
    "$appDir/storage/framework/cache/data",
    "$appDir/storage/framework/sessions",
    "$appDir/storage/framework/views",
    "$appDir/storage/logs",
    "$appDir/bootstrap/cache",
];
foreach ($dirs as $dir) {
    @mkdir($dir, 0775, true);
}

// ── Step 8: Database import ─────────────────────────────────────
$dumpFile = "$homeDir/dump.sql";
if (file_exists($dumpFile)) {
    out("▸ Importing database...");
    exec("mysql -h wheelse281.mysql.db -u wheelse281 -p'sAMgrhg0iq6e9pj' wheelse281 < " . escapeshellarg($dumpFile) . " 2>&1", $output, $ret);
    if ($ret === 0) {
        out("✓ Database imported");
        unlink($dumpFile);
    } else {
        out("⚠ Database import failed (exit $ret)");
        out(implode("\n", $output));
    }
} else {
    out("⚠ No dump.sql found, skipping DB import");
}

// ── Step 9: Artisan commands ────────────────────────────────────
$artisan = "$appDir/artisan";

out("▸ Running migrations...");
exec("php " . escapeshellarg($artisan) . " migrate --force 2>&1", $output, $ret);
out(implode("\n", $output));
$output = [];

out("▸ Clearing caches...");
exec("php " . escapeshellarg($artisan) . " optimize:clear 2>&1", $output);
out(implode("\n", $output));
$output = [];

// NOTE: config:cache and route:cache break OVH shared hosting — .env must be read directly
out("▸ Caching views...");
exec("php " . escapeshellarg($artisan) . " view:cache 2>&1", $output);
out(implode("\n", $output));

// ── Cleanup ─────────────────────────────────────────────────────
exec("rm -rf " . escapeshellarg($tmpDir));
unlink($archive);

out("");
out("✓ DEPLOYMENT COMPLETE");

// Self-delete
unlink(__FILE__);
