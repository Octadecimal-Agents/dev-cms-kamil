<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die(); }
header('Content-Type: text/plain');
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "REDIRECT_URL: " . ($_SERVER['REDIRECT_URL'] ?? 'N/A') . "\n";
$path = $_SERVER['DOCUMENT_ROOT'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
echo "Resolved path: $path\n";
echo "Path.html: $path.html\n";
echo "Is dir: " . (is_dir($path) ? 'YES' : 'NO') . "\n";
echo ".html exists: " . (file_exists("$path.html") ? 'YES' : 'NO') . "\n";
echo "Actual file: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
// Don't self-delete so we can test multiple times
