<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// If user is not an admin, show access denied
if (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) {
    echo "Access denied. You need administrative privileges to view this page.";
    echo "<p><a href='dashboard.php'>Return to dashboard</a></p>";
    exit();
}

// Redirect to admin dashboard
header("Location: admin_reservation.php");
exit();
?>
