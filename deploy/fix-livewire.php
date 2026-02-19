<?php
/**
 * Fix script for Livewire 500 error on 2Wheels Rental.
 * - Clears stale bootstrap cache (config.php, macOS ._ files)
 * - Runs artisan commands using web PHP 8.5 (since CLI is 8.2)
 * - Self-deletes after execution.
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

$appDir = '/home/wheelse/2wheels';
$cacheDir = "$appDir/bootstrap/cache";

out("=== LIVEWIRE FIX ===");
out("PHP: " . PHP_VERSION);
out("");

// ── Step 1: Clear bootstrap cache ──────────────────────────────
out("> Step 1: Clearing bootstrap cache...");

// Remove macOS ._ files
foreach (glob("$cacheDir/._*") as $file) {
    unlink($file);
    out("  Removed: " . basename($file));
}

// Remove cached config (the main suspect)
if (file_exists("$cacheDir/config.php")) {
    // First, let's see what APP_URL and DB_HOST are in the cached config
    $cachedConfig = require "$cacheDir/config.php";
    out("  Cached config APP_URL: " . ($cachedConfig['app']['url'] ?? 'NOT SET'));
    out("  Cached config DB_HOST: " . ($cachedConfig['database']['connections']['mysql']['host'] ?? 'NOT SET'));
    out("  Cached config SESSION_DRIVER: " . ($cachedConfig['session']['driver'] ?? 'NOT SET'));
    out("  Cached config APP_DEBUG: " . (($cachedConfig['app']['debug'] ?? false) ? 'true' : 'false'));

    unlink("$cacheDir/config.php");
    out("  Removed: config.php");
}

// Remove route cache if present
foreach (glob("$cacheDir/routes-v7*.php") as $file) {
    unlink($file);
    out("  Removed: " . basename($file));
}

// Remove event cache if present
if (file_exists("$cacheDir/events.php")) {
    unlink("$cacheDir/events.php");
    out("  Removed: events.php");
}

out("OK: Bootstrap cache cleared");

// ── Step 2: Clear compiled views ──────────────────────────────
out("");
out("> Step 2: Clearing compiled views...");
$viewsDir = "$appDir/storage/framework/views";
$count = 0;
foreach (glob("$viewsDir/*.php") as $file) {
    unlink($file);
    $count++;
}
out("OK: Removed $count compiled views");

// ── Step 3: Run artisan commands via web PHP ──────────────────
out("");
out("> Step 3: Running artisan commands via web PHP bootstrap...");

// We need to bootstrap Laravel's Artisan kernel in this web PHP process
// because CLI PHP (8.2) can't run Laravel 12 (requires 8.4+)
try {
    // Save original server state
    $origArgv = $_SERVER['argv'] ?? [];
    $origArgc = $_SERVER['argc'] ?? 0;

    // Bootstrap Laravel
    $app = require "$appDir/bootstrap/app.php";
    $app->usePublicPath('/home/wheelse/www');

    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

    // Run optimize:clear
    out("  Running: optimize:clear");
    $status = \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    $output = \Illuminate\Support\Facades\Artisan::output();
    out("  " . trim($output));
    out("  Exit code: $status");

    // Run config:cache
    out("  Running: config:cache");
    $status = \Illuminate\Support\Facades\Artisan::call('config:cache');
    $output = \Illuminate\Support\Facades\Artisan::output();
    out("  " . trim($output));
    out("  Exit code: $status");

    // Run view:cache
    out("  Running: view:cache");
    $status = \Illuminate\Support\Facades\Artisan::call('view:cache');
    $output = \Illuminate\Support\Facades\Artisan::output();
    out("  " . trim($output));
    out("  Exit code: $status");

    // Verify the new cached config
    out("");
    out("> Step 4: Verifying new cached config...");
    if (file_exists("$cacheDir/config.php")) {
        // Need to re-read since we just generated it
        $newConfig = require "$cacheDir/config.php";
        out("  New config APP_URL: " . ($newConfig['app']['url'] ?? 'NOT SET'));
        out("  New config DB_HOST: " . ($newConfig['database']['connections']['mysql']['host'] ?? 'NOT SET'));
        out("  New config SESSION_DRIVER: " . ($newConfig['session']['driver'] ?? 'NOT SET'));
        out("  New config APP_DEBUG: " . (($newConfig['app']['debug'] ?? false) ? 'true' : 'false'));
        out("OK: Config cache regenerated");
    } else {
        out("  WARNING: config.php was not generated!");
        out("  Laravel will read .env directly (should still work)");
    }

    // Restore server state
    $_SERVER['argv'] = $origArgv;
    $_SERVER['argc'] = $origArgc;

} catch (\Throwable $e) {
    out("ERROR: " . $e->getMessage());
    out("File: " . $e->getFile() . ":" . $e->getLine());
    out("");
    out("Attempting minimal fix (just clear cache, no regeneration)...");

    // At minimum, make sure config.php is gone so Laravel reads .env directly
    @unlink("$cacheDir/config.php");
    out("OK: Removed config.php - Laravel will read .env directly");
}

// ── Step 5: Test livewire/update endpoint ─────────────────────
out("");
out("> Step 5: Quick sanity check...");

// Verify .env is readable
$envFile = "$appDir/.env";
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    // Check for APP_URL
    if (preg_match('/^APP_URL=(.+)$/m', $envContent, $m)) {
        out("  .env APP_URL: " . trim($m[1]));
    }
    if (preg_match('/^SESSION_DRIVER=(.+)$/m', $envContent, $m)) {
        out("  .env SESSION_DRIVER: " . trim($m[1]));
    }
    if (preg_match('/^DB_HOST=(.+)$/m', $envContent, $m)) {
        out("  .env DB_HOST: " . trim($m[1]));
    }
} else {
    out("  WARNING: .env file missing!");
}

// Check livewire routes are registered
out("");
out("  Checking routes...");
try {
    $routes = app('router')->getRoutes();
    $livewireRoutes = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (str_starts_with($uri, 'livewire')) {
            $livewireRoutes[] = $route->methods()[0] . ' ' . $uri;
        }
    }
    if (count($livewireRoutes) > 0) {
        out("  Livewire routes found: " . implode(', ', $livewireRoutes));
    } else {
        out("  WARNING: No Livewire routes found!");
    }
} catch (\Throwable $e) {
    out("  Could not check routes: " . $e->getMessage());
}

out("");
out("=== FIX COMPLETE ===");
out("Please try logging in again at /admin/login");

// Self-delete
@unlink(__FILE__);
