<?php
include 'config.php';

// Delete all records where logout_time is not null
$sql = "DELETE FROM reservations WHERE logout_time IS NOT NULL";
$conn->query($sql);

$conn->close();

// Redirect back to sit_in_records.php with history tab active
header("Location: sit_in_records.php?tab=history");
exit();
?>
