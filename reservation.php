<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
$sql = "SELECT * FROM users WHERE id_number='$id_number'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_name = $row["first_name"] . ' ' . $row["middle_name"] . ' ' . $row["last_name"];
    $sessions_left = $row["sessions_left"];
} else {
    echo "No user found.";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                    <button type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                    <div class="hidden sm:block sm:ml-6">
                        <div class="flex space-x-4">
                            <a href="dashboard.php"
                                class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Home</a>
                            <a href="edit_student_info.php"
                                class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Profile</a>
                            <a href="history.php"
                                class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">History</a>
                            <a href="reservation.php"
                                class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Reservation</a>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <div class="relative">
                        <button
                            class="bg-gray-800 p-1 rounded-full text-gray-400 hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
                            id="notifications-menu" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">View notifications</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0018 14.5V11a7.003 7.003 0 00-5-6.32V4a2 2 0 10-4 0v.68A7.003 7.003 0 004 11v3.5c0 .419-.141.817-.405 1.095L2 17h5m5 0v2a2 2 0 11-4 0v-2m4 0H9" />
                            </svg>
                        </button>
                        <!-- Dropdown content -->
                        <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 hidden notifications-dropdown"
                            role="menu" aria-orientation="vertical" aria-labelledby="notifications-menu">
                            <!-- Notifications go here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Home</a>
                <a href="edit_student_info.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Profile</a>
                <a href="history.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">History</a>
                <a href="reservation.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Reservation</a>
            </div>
        </div>
    </nav>
    <div>eyy</div>
</body>
</html>
