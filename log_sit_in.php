<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $name = $_POST['student_name'];
    $purpose = $_POST['purpose'];
    $lab_number = $_POST['lab_number'];
    $login_time = date('H:i:s');
    $date = date('Y-m-d');

    $sql = "INSERT INTO active_sit_ins (student_id, name, sit_in_purpose, lab_number, login_time, date) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $student_id, $name, $purpose, $lab_number, $login_time, $date);
    
    if ($stmt->execute()) {
        header("Location: sit_in_records.php");
    } else {
        echo "Error: " . $conn->error;
    }
    
    $stmt->close();
}
$conn->close();
?>
