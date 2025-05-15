<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
$response = [
    'success' => false,
    'message' => '',
    'new_points' => 0,
    'new_sessions' => 0
];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['points_to_convert'])) {
    // Get current points and sessions
    $fetch_user_sql = "SELECT points, sessions_left FROM users WHERE id_number = ?";
    $stmt = $conn->prepare($fetch_user_sql);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $current_points = $user_data['points'];
    $current_sessions = $user_data['sessions_left'];
    
    $points_to_convert = (int)$_POST['points_to_convert'];
    
    // Check if points are valid (multiple of 3)
    if ($points_to_convert % 3 != 0) {
        $response['message'] = 'Points must be in multiples of 3';
    }
    // Check if user has enough points
    elseif ($points_to_convert > $current_points) {
        $response['message'] = 'You do not have enough points to convert';
    }
    elseif ($points_to_convert < 3) {
        $response['message'] = 'You must convert at least 3 points';
    }
    else {
        $sessions_to_add = $points_to_convert / 3;
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Deduct points
            $deduct_points_sql = "UPDATE users SET points = points - ? WHERE id_number = ?";
            $stmt = $conn->prepare($deduct_points_sql);
            $stmt->bind_param("is", $points_to_convert, $id_number);
            $stmt->execute();
            
            // Add sessions
            $add_sessions_sql = "UPDATE users SET sessions_left = sessions_left + ? WHERE id_number = ?";
            $stmt = $conn->prepare($add_sessions_sql);
            $stmt->bind_param("is", $sessions_to_add, $id_number);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Get updated values
            $fetch_updated_sql = "SELECT points, sessions_left FROM users WHERE id_number = ?";
            $stmt = $conn->prepare($fetch_updated_sql);
            $stmt->bind_param("s", $id_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $updated_user = $result->fetch_assoc();
            
            $response['success'] = true;
            $response['message'] = "Successfully converted {$points_to_convert} points to {$sessions_to_add} sessions!";
            $response['new_points'] = $updated_user['points'];
            $response['new_sessions'] = $updated_user['sessions_left'];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $response['message'] = "Error converting points: " . $e->getMessage();
        }
    }
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
?>
