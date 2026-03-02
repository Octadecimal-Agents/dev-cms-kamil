<?php
/**
 * Run artisan commands via web (bypasses CLI PHP version mismatch on OVH).
 * curl "http://2wheels-rental.pl/run-artisan.php?key=2wheels-deploy-2026"
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

$appDir = '/home/wheelse/2wheels';

out("PHP version: " . phpversion());
out("App dir: $appDir\n");

// Bootstrap Laravel
require "$appDir/vendor/autoload.php";

/** @var \Illuminate\Foundation\Application $app */
$app = require "$appDir/bootstrap/app.php";
$app->usePublicPath('/home/wheelse/www');

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

$commands = [
    ['migrate', '--force' => true],
    ['optimize:clear'],
    // NOTE: config:cache and route:cache break OVH shared hosting — .env must be read directly
    ['view:cache'],
];

foreach ($commands as $cmd) {
    $name = is_string($cmd[0] ?? null) ? $cmd[0] : implode(' ', $cmd);
    out("▸ Running: php artisan $name");

    try {
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        if (is_array($cmd) && count($cmd) > 1) {
            $cmdName = array_shift($cmd);
            $exitCode = $kernel->call($cmdName, $cmd, $output);
        } else {
            $cmdName = is_array($cmd) ? $cmd[0] : $cmd;
            $exitCode = $kernel->call($cmdName, [], $output);
        }

        $text = $output->fetch();
        if ($text) out($text);
        out("  → exit code: $exitCode\n");
    } catch (\Throwable $e) {
        out("  ERROR: " . $e->getMessage() . "\n");
    }
}

$kernel->terminate(
    new \Illuminate\Http\Request(),
    new \Illuminate\Http\Response()
);

@unlink(__FILE__);
out("✓ ARTISAN COMMANDS COMPLETE");
