<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');
$src = '/home/wheelse/htaccess-new';
$dst = '/home/wheelse/www/.htaccess';
if (file_exists($src)) {
    copy($src, $dst);
    unlink($src);
    echo "Installed .htaccess (" . filesize($dst) . " bytes)\n";
} else {
    echo "ERROR: $src not found\n";
}
@unlink(__FILE__);
