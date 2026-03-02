<?php
/**
 * Finish deployment — import DB and run artisan commands.
 * curl "http://2wheels-rental.pl/finish-deploy.php?key=2wheels-deploy-2026"
 */
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') {
    http_response_code(403);
    die('Forbidden');
}

set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function out($msg) { echo "$msg\n"; flush(); }

$homeDir  = '/home/wheelse';
$appDir   = "$homeDir/2wheels";
$dumpFile = "$homeDir/dump.sql";

// ── Find PHP binary ─────────────────────────────────────────────
// OVH shared hosting uses versioned paths
$phpPaths = [
    '/usr/local/php8.2/bin/php',
    '/usr/local/php8.3/bin/php',
    '/usr/local/php8.1/bin/php',
    '/usr/local/php8.4/bin/php',
    '/usr/bin/php8.2',
    '/usr/bin/php8.3',
    '/usr/bin/php',
];

$phpBin = null;
foreach ($phpPaths as $p) {
    if (file_exists($p)) {
        $phpBin = $p;
        break;
    }
}

if (!$phpBin) {
    // Try to find it
    $output = [];
    exec("find /usr -name 'php*' -type f 2>/dev/null | head -20", $output);
    out("Could not find PHP binary. Available:");
    out(implode("\n", $output));

    exec("which php 2>&1", $output2);
    out("which php: " . implode("\n", $output2));

    out("\nPHP_BINARY: " . PHP_BINARY);
    out("Current PHP version: " . phpversion());

    // Use PHP_BINARY as fallback
    $phpBin = PHP_BINARY;
}

out("Using PHP: $phpBin (" . phpversion() . ")");

// ── Database import ─────────────────────────────────────────────
if (file_exists($dumpFile)) {
    out("\n▸ Importing database...");
    $output = [];
    exec("mysql -h wheelse281.mysql.db -u wheelse281 -p'sAMgrhg0iq6e9pj' wheelse281 < " . escapeshellarg($dumpFile) . " 2>&1", $output, $ret);
    if ($ret === 0) {
        out("✓ DB imported");
        @unlink($dumpFile);
    } else {
        out("⚠ DB import failed ($ret):");
        out(implode("\n", $output));
    }
} else {
    out("⚠ No dump.sql found");
}

// ── Artisan commands ────────────────────────────────────────────
$art = escapeshellarg("$appDir/artisan");
$cmds = [
    "migrate --force"    => "Running migrations",
    "optimize:clear"     => "Clearing caches",
    // NOTE: config:cache and route:cache break OVH shared hosting — .env must be read directly
    "view:cache"         => "Caching views",
];

foreach ($cmds as $cmd => $label) {
    out("\n▸ $label...");
    $output = [];
    exec("$phpBin $art $cmd 2>&1", $output, $ret);
    out(implode("\n", $output));
    if ($ret !== 0) out("  (exit code: $ret)");
}

// Install root .htaccess if not yet installed
$rootHtaccess = "$homeDir/.htaccess";
if (!file_exists($rootHtaccess) && file_exists("$appDir/deploy/root-htaccess")) {
    copy("$appDir/deploy/root-htaccess", $rootHtaccess);
    out("\n✓ Root .htaccess installed");
}

// Cleanup
@unlink(__FILE__);

out("\n✓ FINISH-DEPLOY COMPLETE");
