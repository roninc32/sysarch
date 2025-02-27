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
    $profile_image = isset($row["profile_image"]) ? $row["profile_image"] : 'assets/images/profile.jpg';
    $last_name = $row["last_name"];
    $first_name = $row["first_name"];
    $middle_name = $row["middle_name"];
    $course_level = $row["course_level"];
    $email = $row["email"];
    $course = $row["course"];
    $address = $row["address"];
} else {
    echo "No user found.";
    exit();
}

$sql_sessions = "SELECT sessions_left FROM users WHERE id_number='$id_number'";
$result_sessions = $conn->query($sql_sessions);

if ($result_sessions->num_rows > 0) {
    $row_sessions = $result_sessions->fetch_assoc();
    $sessions_left = $row_sessions["sessions_left"];
} else {
    $sessions_left = "N/A";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-blue-100 min-h-0 flex flex-col">
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

    <div class="container max-w-7xl mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Student Information -->
            <div class="col-span-1">
                <div class="bg-gray-50 p-6 rounded-lg shadow-lg">
                    <h1 class="text-3xl bg-blue-200 font-bold mb-6 text-center text-gray-800">Student Information</h1>
                    <div class="profile-image-container flex justify-center mb-6">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="w-32 h-32 rounded-full border-4 border-gray-300">
                    </div>
                    <div class="student-info space-y-4">
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Name:</span>
                            <span class="text-gray-900"><?php echo $first_name . ' ' . $middle_name . ' ' . $last_name; ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Course Level:</span>
                            <span class="text-gray-900"><?php echo $course_level; ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Course:</span>
                            <span class="text-gray-900"><?php echo $course; ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Email:</span>
                            <span class="text-gray-900"><?php echo $email; ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Address:</span>
                            <span class="text-gray-900"><?php echo $address; ?></span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-semibold text-gray-700 w-1/3">Sessions Left:</span>
                            <span class="text-gray-900"><?php echo $sessions_left; ?></span>
                        </div>
                    </div>
                    <div class="flex justify-between mt-6">
                        <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700">Logout</a>
                        <!-- Remove Edit Profile Picture button -->
                    </div>
                </div>
            </div>

            <!-- Announcements -->
            <div class="col-span-1">
                <div class="bg-gray-50 p-6 rounded-lg shadow-lg">
                    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Announcements</h1>
                    <div class="space-y-4">
                        <p class="text-lg">No new announcements.</p>
                    </div>
                </div>
            </div>

            <!-- Rules and Regulations -->
            <div class="col-span-1">
                <div class="bg-gray-50 p-6 rounded-lg shadow-lg h-96 overflow-y-auto">
                    <h1 class="text-3xl font-bold mb-6 text-center text-gray-800">Rules and Regulations</h1>
                    <div class="space-y-4 text-lg">
                        <p><strong>LABORATORY RULES AND REGULATIONS</strong></p>
                        <p>To avoid embarrassment and maintain camaraderie with your friends and superiors at our laboratories, please observe the following:</p>
                        <ul class="list-disc pl-6">
                            <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                            <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                            <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                            <li>Getting access to other websites not related to the course (especially pornographic and illicit sites) is strictly prohibited.</li>
                            <li>Deleting computer files and changing the set-up of the computer is a major offense.</li>
                            <li>Observe computer time usage carefully. A fifteen-minute allowance is given for each use. Otherwise, the unit will be given to those who wish to "sit-in".</li>
                            <li>Observe proper decorum while inside the laboratory.</li>
                            <ul class="list-decimal pl-6">
                                <li>Do not get inside the lab unless the instructor is present.</li>
                                <li>All bags, knapsacks, and the likes must be deposited at the counter.</li>
                                <li>Follow the seating arrangement of your instructor.</li>
                                <li>At the end of class, all software programs must be closed.</li>
                                <li>Return all chairs to their proper places after using.</li>
                            </ul>
                            <li>Chewing gum, eating, drinking, smoking, and other forms of vandalism are prohibited inside the lab.</li>
                            <li>Anyone causing a continual disturbance will be asked to leave the lab. Acts or gestures offensive to the members of the community, including public display of physical intimacy, are not tolerated.</li>
                            <li>Persons exhibiting hostile or threatening behavior such as yelling, swearing, or disregarding requests made by lab personnel will be asked to leave the lab.</li>
                            <li>For serious offense, the lab personnel may call the Civil Security Office (CSU) for assistance.</li>
                            <li>Any technical problem or difficulty must be addressed to the laboratory supervisor, student assistant or instructor immediately.</li>
                        </ul>
                        <p><strong>DISCIPLINARY ACTION</strong></p>
                        <p><strong>First Offense:</strong> The Head or the Dean or OIC recommends to the Guidance Center for a suspension from classes for each offender.</p>
                        <p><strong>Second and Subsequent Offenses:</strong> A recommendation for a heavier sanction will be endorsed to the Guidance Center.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>