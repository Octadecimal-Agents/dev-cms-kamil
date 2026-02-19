<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die(); }
header('Content-Type: text/plain');
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "REDIRECT_URL: " . ($_SERVER['REDIRECT_URL'] ?? 'N/A') . "\n";
echo "REDIRECT_STATUS: " . ($_SERVER['REDIRECT_STATUS'] ?? 'N/A') . "\n";
@unlink(__FILE__);
