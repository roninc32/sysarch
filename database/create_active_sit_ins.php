<?php
include '../config.php';

$sql = "CREATE TABLE IF NOT EXISTS active_sit_ins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    sit_in_purpose VARCHAR(255) NOT NULL,
    lab_number VARCHAR(50) NOT NULL,
    login_time TIME NOT NULL,
    date DATE NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table active_sit_ins created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
