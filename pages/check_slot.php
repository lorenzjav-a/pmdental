<?php
// Ensure no accidental white-spaces before or after PHP tags disrupt the JSON payload output
header('Content-Type: application/json');

require_once('../class/database.php');
$db = new database();

$dentist_id = $_GET['dentist_id'] ?? '';
$date = $_GET['date'] ?? '';
$time = $_GET['time'] ?? '';

// Return false immediately if the client payload request parameters are empty
if (empty($dentist_id) || empty($date) || empty($time)) {
    echo json_encode(['taken' => false]);
    exit();
}

try {
    // Call the check function from our database class wrapper instance
    $isTaken = $db->isSlotTaken($dentist_id, $date, $time);
    
    // Output json back to SweetAlert fetch statement
    echo json_encode(['taken' => $isTaken]);
} catch (Exception $e) {
    // If a database connection error occurs, log it and return safe fallback error status
    echo json_encode(['taken' => true, 'error' => $e->getMessage()]);
}