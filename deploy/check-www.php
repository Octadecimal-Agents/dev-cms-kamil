<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$www = '/home/wheelse/www';

echo "=== index.html check ===\n";
echo "index.html exists: " . (file_exists("$www/index.html") ? "YES (" . filesize("$www/index.html") . " bytes)" : "NO") . "\n";
echo "index.php exists: " . (file_exists("$www/index.php") ? "YES" : "NO") . "\n";

echo "\n=== .htaccess first 10 lines ===\n";
$lines = file("$www/.htaccess");
for ($i = 0; $i < min(10, count($lines)); $i++) {
    echo ($i+1) . ": " . $lines[$i];
}

echo "\n=== .htaccess livewire section ===\n";
$content = file_get_contents("$www/.htaccess");
if (preg_match('/Livewire.*?(?=\n\n|\n    #)/s', $content, $m)) {
    echo $m[0] . "\n";
}

echo "\n=== www/ root files ===\n";
foreach (scandir($www) as $f) {
    if ($f === '.' || $f === '..') continue;
    $path = "$www/$f";
    if (is_dir($path)) echo "  [DIR] $f\n";
    elseif (is_link($path)) echo "  [LNK] $f -> " . readlink($path) . "\n";
    else echo "  $f (" . filesize($path) . " bytes)\n";
}

@unlink(__FILE__);
