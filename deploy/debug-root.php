<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die(); }
header('Content-Type: text/plain');
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
$testPath = $_SERVER['DOCUMENT_ROOT'] . '/www/motocykle/bmw-m1000r-competition.html';
echo "Test path: $testPath\n";
echo "Exists: " . (file_exists($testPath) ? 'YES' : 'NO') . "\n";
echo "Is file: " . (is_file($testPath) ? 'YES' : 'NO') . "\n";
echo "\nwww/motocykle/ listing:\n";
$files = scandir($_SERVER['DOCUMENT_ROOT'] . '/www/motocykle/');
foreach ($files as $f) echo "  $f\n";
