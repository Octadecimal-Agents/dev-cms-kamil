<?php
/**
 * Diagnostic script for 2Wheels Rental — reads Laravel error log
 * Self-deletes after execution.
 */
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');

$appDir = '/home/wheelse/2wheels';
$logFile = "$appDir/storage/logs/laravel.log";

echo "=== 2WHEELS DIAGNOSTICS ===\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// Check log file
echo "--- LARAVEL LOG (last 200 lines) ---\n";
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last = array_slice($lines, -200);
    echo implode('', $last);
} else {
    echo "Log file not found at: $logFile\n";
    // Try to find any log files
    $logDir = "$appDir/storage/logs";
    if (is_dir($logDir)) {
        echo "Files in $logDir:\n";
        foreach (scandir($logDir) as $f) {
            if ($f !== '.' && $f !== '..') {
                echo "  $f (" . filesize("$logDir/$f") . " bytes)\n";
            }
        }
    } else {
        echo "Log directory does not exist!\n";
    }
}

echo "\n\n--- SESSIONS TABLE CHECK ---\n";
// Try to connect to MySQL and check sessions table
try {
    $pdo = new PDO(
        'mysql:host=wheelse281.mysql.db;port=3306;dbname=wheelse281',
        'wheelse281',
        'sAMgrhg0iq6e9pj'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DB connection: OK\n";

    // Check sessions table
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->rowCount() > 0) {
        echo "Sessions table: EXISTS\n";
        $stmt = $pdo->query("DESCRIBE sessions");
        echo "Sessions columns:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  {$row['Field']} ({$row['Type']})\n";
        }
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM sessions");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Sessions count: {$row['cnt']}\n";
    } else {
        echo "Sessions table: MISSING!\n";
    }

    // Check cache table
    $stmt = $pdo->query("SHOW TABLES LIKE 'cache'");
    echo "Cache table: " . ($stmt->rowCount() > 0 ? "EXISTS" : "MISSING") . "\n";

    // Check jobs table
    $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
    echo "Jobs table: " . ($stmt->rowCount() > 0 ? "EXISTS" : "MISSING") . "\n";

} catch (PDOException $e) {
    echo "DB connection FAILED: " . $e->getMessage() . "\n";
}

echo "\n\n--- FILE PERMISSIONS ---\n";
foreach ([
    "$appDir/storage",
    "$appDir/storage/logs",
    "$appDir/storage/framework",
    "$appDir/storage/framework/sessions",
    "$appDir/storage/framework/cache",
    "$appDir/storage/framework/views",
    "$appDir/bootstrap/cache",
] as $dir) {
    if (is_dir($dir)) {
        echo sprintf("  %s → %s (writable: %s)\n", $dir, substr(decoct(fileperms($dir)), -4), is_writable($dir) ? 'YES' : 'NO');
    } else {
        echo "  $dir → MISSING!\n";
    }
}

echo "\n\n--- .ENV CHECK ---\n";
$envFile = "$appDir/.env";
if (file_exists($envFile)) {
    echo ".env exists: YES\n";
    $env = parse_ini_file($envFile);
    echo "APP_ENV: " . ($env['APP_ENV'] ?? 'not set') . "\n";
    echo "APP_DEBUG: " . ($env['APP_DEBUG'] ?? 'not set') . "\n";
    echo "APP_URL: " . ($env['APP_URL'] ?? 'not set') . "\n";
    echo "SESSION_DRIVER: " . ($env['SESSION_DRIVER'] ?? 'not set') . "\n";
    echo "DB_HOST: " . ($env['DB_HOST'] ?? 'not set') . "\n";
    echo "CACHE_STORE: " . ($env['CACHE_STORE'] ?? 'not set') . "\n";
} else {
    echo ".env exists: NO!\n";
}

echo "\n\n--- BOOTSTRAP CACHE ---\n";
$cacheDir = "$appDir/bootstrap/cache";
if (is_dir($cacheDir)) {
    foreach (scandir($cacheDir) as $f) {
        if ($f !== '.' && $f !== '..') {
            $path = "$cacheDir/$f";
            echo "  $f (" . (is_dir($path) ? 'dir' : filesize($path) . " bytes") . ")\n";
        }
    }
} else {
    echo "Bootstrap cache dir missing!\n";
}

echo "\n=== DIAGNOSTICS COMPLETE ===\n";

// Self-delete
@unlink(__FILE__);
