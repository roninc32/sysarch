<?php
session_start();
include 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_logged_in"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $sql = "SELECT * FROM lab_schedules WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($schedule = $result->fetch_assoc()) {
        // Format times to HH:mm for HTML time input
        $schedule['start_time'] = date('H:i', strtotime($schedule['start_time']));
        $schedule['end_time'] = date('H:i', strtotime($schedule['end_time']));
        
        header('Content-Type: application/json');
        echo json_encode($schedule);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Schedule not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing schedule ID']);
}

$conn->close();
?>
