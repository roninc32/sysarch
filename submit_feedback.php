<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated']);
    exit();
}

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get and sanitize input data
$reservationId = isset($_POST['reservationId']) ? intval($_POST['reservationId']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$hadIssues = isset($_POST['hadIssues']) && $_POST['hadIssues'] === '1';
$issuesDescription = isset($_POST['issuesDescription']) ? trim($_POST['issuesDescription']) : '';
$userId = $_SESSION["username"]; // Using session username as user_id

// Validate required fields
if ($reservationId <= 0 || $rating <= 0 || $rating > 5) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid input data']);
    exit();
}

// Get reservation details to store lab number
$sql_reservation = "SELECT lab_number FROM reservations WHERE id = ?";
$stmt_reservation = $conn->prepare($sql_reservation);
$stmt_reservation->bind_param("i", $reservationId);
$stmt_reservation->execute();
$result_reservation = $stmt_reservation->get_result();

if ($result_reservation->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Reservation not found']);
    exit();
}

$reservation = $result_reservation->fetch_assoc();
$labNumber = $reservation['lab_number'];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Insert feedback
    $sql = "INSERT INTO feedback (reservation_id, user_id, lab_number, rating, message, had_issues, issues_description) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $hadIssuesInt = $hadIssues ? 1 : 0;
    $stmt->bind_param("issisis", $reservationId, $userId, $labNumber, $rating, $message, $hadIssuesInt, $issuesDescription);
    $stmt->execute();
    
    // Update reservation to mark that feedback has been provided
    $sql_update = "UPDATE reservations SET has_feedback = 1 WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $reservationId);
    $stmt_update->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully']);
    
} catch (Exception $e) {
    // Roll back transaction in case of error
    $conn->rollback();
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
