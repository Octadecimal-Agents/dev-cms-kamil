<?php
/**
 * Fix deployment — move web files from www/ to home directory (the real document root).
 * curl "http://2wheels-rental.pl/fix-deploy.php?key=2wheels-deploy-2026"
 */
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') {
    http_response_code(403);
    die('Forbidden');
}

set_time_limit(120);
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
if (ob_get_level()) ob_end_flush();

function out($msg) { echo "$msg\n"; flush(); }

$homeDir = '/home/wheelse';
$wwwDir  = "$homeDir/www";
$appDir  = "$homeDir/2wheels";

out("Document root: " . $_SERVER['DOCUMENT_ROOT']);
out("Moving web files from www/ to home directory...\n");

// Remove root .htaccess (was redirecting to www/)
if (file_exists("$homeDir/.htaccess")) {
    unlink("$homeDir/.htaccess");
    out("✓ Removed root .htaccess redirect");
}

// Copy all files from www/ to home directory
$items = scandir($wwwDir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $src = "$wwwDir/$item";
    $dst = "$homeDir/$item";

    // Skip if destination already exists and is the same
    if ($item === 'storage' && is_link($src)) {
        // Recreate symlink at home level
        if (is_link($dst)) unlink($dst);
        elseif (file_exists($dst)) exec("rm -rf " . escapeshellarg($dst));
        symlink("2wheels/storage/app/public", $dst);
        out("  symlink: $item → 2wheels/storage/app/public");
        continue;
    }

    // Remove destination if exists
    if (is_link($dst)) unlink($dst);
    elseif (is_dir($dst)) exec("rm -rf " . escapeshellarg($dst));
    elseif (file_exists($dst)) unlink($dst);

    // Move
    rename($src, $dst);
    out("  moved: $item");
}

// Update index.php paths (../2wheels/ stays correct since we're still one level above 2wheels/)
// Actually, index.php uses __DIR__.'/../2wheels/' which will now look for /home/wheelse/../2wheels/
// That's wrong — it should be /home/wheelse/2wheels/. Let me fix the paths.
$indexPhp = file_get_contents("$homeDir/index.php");
$indexPhp = str_replace('../2wheels/', 'PLACEHOLDER_DIR/', $indexPhp);

// Write a corrected index.php
$correctedIndex = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/2wheels/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/2wheels/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/2wheels/bootstrap/app.php';

// Tell Laravel that this directory is the public directory
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
PHP;

file_put_contents("$homeDir/index.php", $correctedIndex);
out("\n✓ Updated index.php paths (./2wheels/ instead of ../2wheels/)");

// Fix storage symlink
if (!is_link("$homeDir/storage")) {
    symlink("2wheels/storage/app/public", "$homeDir/storage");
    out("✓ Fixed storage symlink");
}

// Clean up the now-empty www/ directory
exec("rm -rf " . escapeshellarg($wwwDir));
out("✓ Removed empty www/ directory");

// Clean up temp deploy files
foreach (glob("$homeDir/trigger-deploy.php") as $f) @unlink($f);
foreach (glob("$homeDir/finish-deploy.php") as $f) @unlink($f);
foreach (glob("$homeDir/debug-*.php") as $f) @unlink($f);
foreach (glob("$homeDir/remote-install.sh") as $f) @unlink($f);

@unlink(__FILE__);

out("\n✓ FIX COMPLETE — web files now in document root (/home/wheelse/)");
