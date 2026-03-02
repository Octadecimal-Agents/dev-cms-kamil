<?php
/**
 * Trigger deployment — upload to /home/wheelse/ document root.
 * curl "http://2wheels-rental.pl/trigger-deploy.php?key=2wheels-deploy-2026"
 */
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') {
    http_response_code(403);
    die('Forbidden');
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

$homeDir = '/home/wheelse';
$appDir  = "$homeDir/2wheels";
$wwwDir  = "$homeDir/www";
$archive = "$homeDir/2wheels-deploy.tar.gz";
$dumpFile = "$homeDir/dump.sql";
$pid = getmypid();
$tmpDir  = "$homeDir/tmp-deploy-$pid";

function out($msg) { echo "$msg\n"; flush(); }

// ── Clean up old temp dirs from failed runs ─────────────────────
foreach (glob("$homeDir/tmp-deploy-*") as $old) {
    if (is_dir($old)) exec("rm -rf " . escapeshellarg($old));
}

// ── 1. Extract ──────────────────────────────────────────────────
if (!file_exists($archive)) { out("ERROR: $archive not found"); exit(1); }

out("▸ Extracting archive...");
@mkdir($tmpDir, 0755, true);
$output = []; exec("tar xzf " . escapeshellarg($archive) . " -C " . escapeshellarg($tmpDir) . " 2>&1", $output, $ret);
if ($ret !== 0) { out("ERROR: tar failed ($ret)\n" . implode("\n", $output)); exit(1); }
out("✓ Extracted");

// ── 2. Laravel app ──────────────────────────────────────────────
out("▸ Installing Laravel app → $appDir");
exec("rm -rf " . escapeshellarg($appDir));
$output = []; exec("mv " . escapeshellarg("$tmpDir/laravel") . " " . escapeshellarg($appDir) . " 2>&1", $output, $ret);
if ($ret !== 0) { out("ERROR: mv failed ($ret)\n" . implode("\n", $output)); exit(1); }
out("✓ Laravel installed");

// ── 3. Next.js → www/ ──────────────────────────────────────────
out("▸ Installing Next.js static files → $wwwDir");
// Preserve .ovhconfig
$ovhCfg = file_exists("$wwwDir/.ovhconfig") ? file_get_contents("$wwwDir/.ovhconfig") : null;

// Clean www/
foreach (array_diff(scandir($wwwDir), ['.', '..']) as $item) {
    $p = "$wwwDir/$item";
    is_dir($p) ? exec("rm -rf " . escapeshellarg($p)) : @unlink($p);
}

if (is_dir("$tmpDir/out")) {
    exec("cp -r " . escapeshellarg("$tmpDir/out") . "/* " . escapeshellarg($wwwDir) . "/ 2>&1");
    out("✓ Next.js files copied");
} else {
    out("⚠ No out/ in archive");
}

if ($ovhCfg !== null) file_put_contents("$wwwDir/.ovhconfig", $ovhCfg);

// ── 4. Deploy files ─────────────────────────────────────────────
out("▸ Installing index.php, .htaccess...");
copy("$appDir/deploy/index.php", "$wwwDir/index.php");
copy("$appDir/deploy/.htaccess", "$wwwDir/.htaccess");

// Root .htaccess (OVH docroot = /home/wheelse/, rewrite to www/)
if (file_exists("$appDir/deploy/root-htaccess")) {
    copy("$appDir/deploy/root-htaccess", "$homeDir/.htaccess");
    out("✓ Root .htaccess installed");
}

// ── 5. .env ─────────────────────────────────────────────────────
out("▸ Installing .env...");
copy("$appDir/.env.prd", "$appDir/.env");

// ── 6. Storage symlink ──────────────────────────────────────────
out("▸ Storage symlink...");
$link = "$wwwDir/storage";
if (is_link($link)) unlink($link);
elseif (file_exists($link)) exec("rm -rf " . escapeshellarg($link));
symlink("../2wheels/storage/app/public", $link);

// ── 7. Permissions & dirs ───────────────────────────────────────
out("▸ Permissions...");
exec("chmod -R 775 " . escapeshellarg("$appDir/storage") . " " . escapeshellarg("$appDir/bootstrap/cache") . " 2>&1");
foreach ([
    "$appDir/storage/framework/cache/data",
    "$appDir/storage/framework/sessions",
    "$appDir/storage/framework/views",
    "$appDir/storage/logs",
    "$appDir/bootstrap/cache",
] as $d) @mkdir($d, 0775, true);

// ── 8. Database ─────────────────────────────────────────────────
if (file_exists($dumpFile)) {
    out("▸ Importing database...");
    $output = [];
    exec("mysql -h wheelse281.mysql.db -u wheelse281 -p'sAMgrhg0iq6e9pj' wheelse281 < " . escapeshellarg($dumpFile) . " 2>&1", $output, $ret);
    if ($ret === 0) { out("✓ DB imported"); @unlink($dumpFile); }
    else out("⚠ DB import failed ($ret): " . implode("\n", $output));
} else {
    out("⚠ No dump.sql, skipping");
}

// ── 9. Artisan ──────────────────────────────────────────────────
$art = escapeshellarg("$appDir/artisan");
$cmds = [
    "migrate --force"    => "Running migrations",
    "optimize:clear"     => "Clearing caches",
    // NOTE: config:cache breaks OVH shared hosting — .env must be read directly
    "view:cache"         => "Caching views",
];
foreach ($cmds as $cmd => $label) {
    out("▸ $label...");
    $output = [];
    exec("php $art $cmd 2>&1", $output, $ret);
    out(implode("\n", $output));
}

// ── Cleanup ─────────────────────────────────────────────────────
exec("rm -rf " . escapeshellarg($tmpDir));
@unlink($archive);
@unlink(__FILE__); // Self-delete

out("\n✓ DEPLOYMENT COMPLETE");
