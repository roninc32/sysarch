<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    // Delete the reservation record
    $sql_delete_reservation = "DELETE FROM reservations WHERE id = ?";
    $stmt = $conn->prepare($sql_delete_reservation);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the sit-in records page
    header("Location: sit_in_records.php");
    exit();
}
?>
