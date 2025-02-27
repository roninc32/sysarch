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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $last_name = $_POST["last_name"];
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"];
    $course_level = $_POST["course_level"];
    $email = $_POST["email"];
    $course = $_POST["course"];
    $address = $_POST["address"];

    $sql = "UPDATE users SET last_name='$last_name', first_name='$first_name', middle_name='$middle_name', course_level='$course_level', email='$email', course='$course', address='$address' WHERE id_number='$id_number'";

    if ($conn->query($sql) === TRUE) {
        $_SESSION["update_success"] = true;
        // Redefine the query to fetch the updated information from the database
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
        }
    } else {
        echo "Error updating record: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Information</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .fade-out {
            transition: opacity 1s ease-out;
            opacity: 0;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                    <button type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        aria-controls="mobile-menu" aria-expanded="false">
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

    <div class="container max-w-2xl mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow">
        <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Edit Student Information</h1>
        <?php if (isset($update_success) && $update_success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">Your profile has been updated.</span>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION["update_success"]) && $_SESSION["update_success"]): ?>
            <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
                <strong class="font-bold">Success!</strong>
                <span class="block sm:inline">Updated Successfully!</span>
            </div>
            <?php unset($_SESSION["update_success"]); ?>
        <?php endif; ?>
        <div class="flex justify-center mb-6">
            <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="w-32 h-32 rounded-full border-4 border-gray-300">
        </div>
        <form method="post" action="edit_student_info.php" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($last_name); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($first_name); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($middle_name); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                </div>
                <div>
                    <label for="course_level" class="block text-sm font-medium text-gray-700">Course Level</label>
                    <select id="course_level" name="course_level" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="1st Year" <?php if ($course_level == '1st Year') echo 'selected'; ?>>1st Year</option>
                        <option value="2nd Year" <?php if ($course_level == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                        <option value="3rd Year" <?php if ($course_level == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                        <option value="4th Year" <?php if ($course_level == '4th Year') echo 'selected'; ?>>4th Year</option>
                        <option value="5th Year" <?php if ($course_level == '5th Year') echo 'selected'; ?>>5th Year</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="course" class="block text-sm font-medium text-gray-700">Course</label>
                <select id="course" name="course" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="BSCS" <?php if ($course == 'BSCS') echo 'selected'; ?>>BSCS</option>
                    <option value="BSIT" <?php if ($course == 'BSIT') echo 'selected'; ?>>BSIT</option>
                    <option value="BSSE" <?php if ($course == 'BSSE') echo 'selected'; ?>>BSSE</option>
                </select>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                <textarea id="address" name="address" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
            <div class="flex justify-between mt-6">
                <input type="submit" value="Save Changes" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                <button type="button" onclick="toggleEditProfile()" class="bg-indigo-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700">Edit Profile Picture</button>
            </div>
        </form>
    </div>

    <div id="editProfileModal" class="modal hidden fixed inset-0 bg-gray-500 bg-opacity-50 flex justify-center items-center">
        <div class="modal-content bg-white p-8 rounded-lg shadow-lg max-w-lg w-full relative">
            <!-- Close button -->
            <button class="absolute top-2 right-2 bg-gray-300 text-gray-700 p-2 rounded-full hover:bg-gray-400 focus:outline-none" onclick="toggleEditProfile()">
                &times;
            </button>

            <h2 class="text-2xl font-semibold mb-4">Edit Profile</h2>
            <form method="post" action="edit_profile.php" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label for="profile_image" class="block text-sm font-medium text-gray-700">Profile Image</label>
                    <input type="file" id="profile_image" name="profile_image" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <div class="flex justify-end">
                    <input type="submit" value="Save Changes" class="bg-indigo-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700">
                </div>
            </form>
        </div>
    </div>
    <script src="assets/js/scripts.js"></script>
</body>
</html>
