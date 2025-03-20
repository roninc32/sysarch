<?php
include 'config.php';

// Add form processing code at the top
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST["id_number"];
    $last_name = $_POST["last_name"];
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"];
    $course_level = $_POST["course_level"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $email = $_POST["email"];
    $course = $_POST["course"];
    $address = $_POST["address"];
    $profile_image = 'assets/images/profile.jpg';

    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Check if user already exists
        $sql_check = "SELECT * FROM users WHERE id_number=? OR email=?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $id_number, $email);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows > 0) {
            echo "<script>alert('An account with this ID number or email already exists.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, password, email, course, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt->prepare($sql);
            $stmt->bind_param("ssssssssss", $id_number, $last_name, $first_name, $middle_name, $course_level, $hashed_password, $email, $course, $address, $profile_image);

            if ($stmt->execute()) {
                echo "<script>alert('Student registered successfully!');</script>";
            } else {
                echo "<script>alert('Error registering student: " . $stmt->error . "');</script>";
            }
        }
    }
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_query = "SELECT COUNT(*) as count FROM users";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch student records with pagination
$sql_students = "SELECT * FROM users ORDER BY id_number LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql_students);
if ($stmt) {
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result_students = $stmt->get_result();
    $students = $result_students->fetch_all(MYSQLI_ASSOC);
} else {
    echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
    $students = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-container {
            @apply bg-gradient-to-r from-indigo-600 to-blue-500 shadow-lg;
        }
        
        .nav-link {
            @apply px-4 py-2 text-white hover:text-white/90 font-medium transition-all duration-200
                relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 
                after:bg-white after:transition-all after:duration-200 hover:after:w-full;
        }
        
        .nav-link.active {
            @apply text-white after:w-full font-bold;
        }
        
        .logout-btn {
            @apply px-4 py-2 text-white border-2 border-white/80 rounded-lg 
                hover:bg-white hover:text-indigo-600 transition-all duration-200
                font-medium focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2
                focus:ring-offset-indigo-600;
        }

        .nav-brand {
            @apply flex items-center space-x-3 text-white;
        }

        .nav-brand-text {
            @apply text-lg font-bold hidden md:block;
        }
    </style>
</head>
<body class="bg-gradient-to-r from-blue-100 via-blue-300 to-blue-500 min-h-screen flex flex-col" style="background-image: url('assets/images/bg.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <header class="w-full top-0 z-50">
        <nav class="nav-container">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="nav-brand">
                            <img class="h-10 w-auto" src="assets/images/ccs-logo.png" alt="CCS Logo">
                        </div>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-home mr-2"></i>Dashboard
                                </a>
                                <a href="student_record.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-users mr-2"></i>Students
                                </a>
                                <a href="sit_in_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-clipboard-list mr-2"></i>Sit-in Records
                                </a>
                                <a href="search_student.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-search mr-2"></i>Search Student
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="admin_logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto p-6 lg:p-8 flex-grow">
        <div class="bg-white bg-opacity-90 rounded-xl shadow-2xl p-6 lg:p-8 backdrop-blur-lg">
            <h1 class="text-4xl font-bold text-gray-800 mb-8 text-center">Student Records</h1>
            <div class="mb-6 flex justify-end">
                <button onclick="openModal()" class="bg-indigo-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700">
                    <i class="fas fa-user-plus mr-2"></i>Register New Student
                </button>
            </div>
            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Middle Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions Left</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['id_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['first_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['middle_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['last_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($student['sessions_left']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No student records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>" class="px-3 py-1 bg-white text-gray-500 rounded-md border border-gray-300 hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        // Calculate range of page numbers to show
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Show first page if we're not starting at 1
                        if ($start_page > 1) {
                            echo '<a href="?page=1" class="px-3 py-1 bg-white text-gray-500 rounded-md border border-gray-300 hover:bg-gray-50">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="px-3 py-1">...</span>';
                            }
                        }

                        // Show page numbers
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="px-3 py-1 bg-blue-500 text-white rounded-md">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . '" class="px-3 py-1 bg-white text-gray-500 rounded-md border border-gray-300 hover:bg-gray-50">' . $i . '</a>';
                            }
                        }

                        // Show last page if we're not ending at total_pages
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="px-3 py-1">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '" class="px-3 py-1 bg-white text-gray-500 rounded-md border border-gray-300 hover:bg-gray-50">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>" class="px-3 py-1 bg-white text-gray-500 rounded-md border border-gray-300 hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>

            <!-- Records info display -->
            <div class="mt-4 text-center text-sm text-gray-600">
                Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> to 
                <?php echo min($page * $records_per_page, $total_records); ?> of 
                <?php echo $total_records; ?> records
            </div>
        </div>
    </main>

    <footer class="text-center p-4 text-white mt-8">
        <p>&copy; <?php echo date("Y"); ?> All rights reserved.</p>
    </footer>

    <!-- Registration Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Register New Student</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmRegistration()">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Number</label>
                        <input type="text" name="id_number" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" name="middle_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Level</label>
                        <select name="course_level" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="1st Year">1</option>
                            <option value="2nd Year">2</option>
                            <option value="3rd Year">3</option>
                            <option value="4th Year">4</option>
                            <option value="5th Year">5</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course</label>
                        <select name="course" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-700">Cancel</button>
                        <button type="submit" class="bg-indigo-500 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('registrationModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('registrationModal').classList.add('hidden');
        }

        function confirmRegistration() {
            return confirm('Are you sure you want to register this student?');
        }
    </script>
</body>
</html>
