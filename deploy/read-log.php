<?php
if (($_GET['key'] ?? '') !== '2wheels-deploy-2026') { http_response_code(403); die('Forbidden'); }
header('Content-Type: text/plain; charset=utf-8');

$logFile = '/home/wheelse/2wheels/storage/logs/laravel.log';
if (!file_exists($logFile)) { die("No log file"); }

$lines = file($logFile);
// Get last 100 lines
$last = array_slice($lines, -100);
echo implode('', $last);

// Self-delete
@unlink(__FILE__);
