<?php
// Simple debug logging endpoint
header('Content-Type: application/json');

// Get the log data
$input = file_get_contents('php://input');
$logData = json_decode($input, true);

if ($logData) {
    $logMessage = date('Y-m-d H:i:s') . ' - ' . $logData['message'];
    if ($logData['data'] !== null) {
        $logMessage .= ' - ' . json_encode($logData['data']);
    }
    $logMessage .= "\n";
    
    // Write to a debug log file
    file_put_contents(__DIR__ . '/debug_frontend.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid log data']);
}
?> 