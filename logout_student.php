<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];
    $logout_time = date('H:i:s');
    
    // Get the active sit-in record
    $sql_select = "SELECT * FROM active_sit_ins WHERE id = ?";
    $stmt = $conn->prepare($sql_select);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sit_in = $result->fetch_assoc();
    
    // Insert into reservations history
    $sql_insert = "INSERT INTO reservations (id_number, name, sit_in_purpose, lab_number, login_time, logout_time, date) 
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sssssss", 
        $sit_in['student_id'], 
        $sit_in['name'], 
        $sit_in['sit_in_purpose'], 
        $sit_in['lab_number'], 
        $sit_in['login_time'],
        $logout_time,
        $sit_in['date']
    );
    
    if ($stmt_insert->execute()) {
        // Delete from active_sit_ins
        $sql_delete = "DELETE FROM active_sit_ins WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id);
        $stmt_delete->execute();
        
        header("Location: sit_in_records.php");
    } else {
        echo "Error updating record: " . $conn->error;
    }
    
    $stmt->close();
    $stmt_insert->close();
    $stmt_delete->close();
}

$conn->close();
?>
