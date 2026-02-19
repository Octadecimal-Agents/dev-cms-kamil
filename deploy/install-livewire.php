<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$home = '/home/wheelse';
$www = "$home/www";

// Create livewire directory
$lwDir = "$www/livewire";
if (!is_dir($lwDir)) {
    mkdir($lwDir, 0755, true);
    echo "Created: $lwDir\n";
}

// Move files from home dir to livewire dir
foreach (['livewire.min.js', 'manifest.json'] as $f) {
    $src = "$home/$f";
    $dst = "$lwDir/$f";
    if (file_exists($src)) {
        rename($src, $dst);
        echo "Installed: $f (" . filesize($dst) . " bytes)\n";
    } else {
        echo "NOT FOUND: $src\n";
    }
}

// Install updated .htaccess
$htSrc = "$home/htaccess-new";
if (file_exists($htSrc)) {
    copy($htSrc, "$www/.htaccess");
    unlink($htSrc);
    echo "Installed: .htaccess\n";
} else {
    echo "NOT FOUND: $htSrc\n";
}

// Verify
echo "\nVerification:\n";
echo "livewire.min.js exists: " . (file_exists("$lwDir/livewire.min.js") ? "YES (" . filesize("$lwDir/livewire.min.js") . " bytes)" : "NO") . "\n";
echo "manifest.json exists: " . (file_exists("$lwDir/manifest.json") ? "YES" : "NO") . "\n";

// Test that .htaccess has the static livewire rule
$htaccess = file_get_contents("$www/.htaccess");
echo ".htaccess has static livewire rule: " . (str_contains($htaccess, 'Serve Livewire static assets directly') ? "YES" : "NO") . "\n";

@unlink(__FILE__);
echo "\nDone\n";
