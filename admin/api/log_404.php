<?php
// File for logging 404 errors

// Set headers for security
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Get the data
$page = $_POST['page'] ?? 'unknown';
$referrer = $_POST['referrer'] ?? 'none';
$timestamp = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

// Create log entry
$logEntry = "$timestamp | Page: $page | Referrer: $referrer | IP: $ip | UA: $userAgent\n";

// Log to file
$logFile = __DIR__ . '/../logs/404_errors.log';

// Make sure the directory exists
$logDirectory = dirname($logFile);
if (!is_dir($logDirectory)) {
    mkdir($logDirectory, 0755, true);
}

// Append log entry
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Return success
echo json_encode(['success' => true]);
?>