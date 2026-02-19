<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain');

echo "PHP_BINARY: " . PHP_BINARY . "\n";
echo "phpversion(): " . phpversion() . "\n";
echo "php_sapi_name(): " . php_sapi_name() . "\n\n";

echo "--- Looking for PHP binaries ---\n";
$output = [];
exec("find /usr/local -name 'php' -o -name 'php8*' 2>/dev/null | sort", $output);
echo implode("\n", $output) . "\n\n";

echo "--- Version checks ---\n";
$candidates = array_merge($output, [PHP_BINARY, '/usr/bin/php']);
$seen = [];
foreach ($candidates as $bin) {
    $bin = trim($bin);
    if (!$bin || isset($seen[$bin]) || !file_exists($bin)) continue;
    $seen[$bin] = true;
    $v = [];
    exec("$bin -v 2>&1 | head -1", $v);
    echo "$bin → " . ($v[0] ?? 'error') . "\n";
}

@unlink(__FILE__);
