<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
$sql = "SELECT * FROM reservations WHERE id_number='$id_number'";
$result = $conn->query($sql);

$reservations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation History</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-blue-50 min-h-screen flex flex-col">
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
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Edit
                    Student Information</a>
                <a href="history.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">History</a>
                <a href="reservation.php"
                    class="text-gray-900 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium">Reservation</a>
            </div>
        </div>
    </nav>

    <div class="container max-w-4xl mx-auto bg-gradient-to-r from-blue-100 to-blue-200 p-8 rounded-lg shadow-lg mt-8 flex-grow">
        <div class="bg-blue-100 rounded-lg shadow-lg">
            <div class="bg-blue-200 p-6 rounded-t-lg">
                <h1 class="text-3xl font-bold text-center text-gray-800">Reservation History</h1>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <input type="text" id="search" placeholder="Search..." class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">ID Number</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Name</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Sit-in Purpose</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Laboratory Number</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Login Time</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Logout Time</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Date</th>
                                <th class="py-2 px-4 border-b text-left text-sm font-semibold text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody id="reservationTable">
                            <?php if (empty($reservations)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-gray-500">No reservation history found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($reservations as $reservation): ?>
                                    <tr class="hover:bg-gray-100">
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['id_number']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['name']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['sit_in_purpose']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['lab_number']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['login_time']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['logout_time']); ?></td>
                                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($reservation['date']); ?></td>
                                        <td class="py-2 px-4 border-b"><a href="action.php?id=<?php echo $reservation['id']; ?>" class="text-blue-500 hover:underline">Action</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/scripts.js"></script>
</body>
</html>
