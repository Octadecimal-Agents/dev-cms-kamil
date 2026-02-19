<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$dir = '/home/wheelse/www/livewire';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "Created: $dir\n";
} else {
    echo "Already exists: $dir\n";
}

// Also copy from vendor if available (fallback)
$vendorDir = '/home/wheelse/2wheels/public/vendor/livewire';
if (is_dir($vendorDir)) {
    foreach (['livewire.min.js', 'manifest.json'] as $f) {
        if (file_exists("$vendorDir/$f")) {
            copy("$vendorDir/$f", "$dir/$f");
            echo "Copied from vendor: $f\n";
        }
    }
}

echo "Dir contents:\n";
foreach (scandir($dir) as $f) {
    if ($f !== '.' && $f !== '..') {
        echo "  $f (" . filesize("$dir/$f") . " bytes)\n";
    }
}

@unlink(__FILE__);
echo "Done\n";
