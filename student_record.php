<?php
include 'config.php';

// Add form processing code at the top
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_number"])) {
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
            $sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, password, confirm_password, email, course, address, profile_image, sessions_left) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 30)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssss", 
                $id_number, 
                $last_name, 
                $first_name, 
                $middle_name, 
                $course_level, 
                $hashed_password, 
                $hashed_password, // Using the same hashed password for confirm_password
                $email, 
                $course, 
                $address, 
                $profile_image
            );

            if ($stmt->execute()) {
                echo "<script>alert('Student registered successfully!');</script>";
            } else {
                echo "<script>alert('Error registering student: " . $stmt->error . "');</script>";
            }
        }
    }
}

// Handle individual session reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_individual_session'])) {
    $student_id = $_POST['student_id'];
    
    $reset_sql = "UPDATE users SET sessions_left = 30 WHERE id_number = ?";
    $stmt = $conn->prepare($reset_sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Sessions reset to 30 successfully for student ID: " . htmlspecialchars($student_id) . "');</script>";
        // Redirect to refresh the page
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error resetting sessions: " . $stmt->error . "');</script>";
    }
}

// Handle reset all sessions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_sessions'])) {
    $reset_sql = "UPDATE users SET sessions_left = 30";
    $stmt = $conn->prepare($reset_sql);
    
    if ($stmt->execute()) {
        echo "<script>alert('All sessions have been reset to 30 successfully!');</script>";
        // Redirect to refresh the page
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error resetting sessions: " . $stmt->error . "');</script>";
    }
}

// Handle student deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];
    
    $delete_sql = "DELETE FROM users WHERE id_number = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("s", $student_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Student deleted successfully!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error deleting student: " . $stmt->error . "');</script>";
    }
}

// Handle student update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    $student_id = $_POST['student_id'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $course_level = $_POST['course_level'];
    $email = $_POST['email'];
    $course = $_POST['course'];
    $address = $_POST['address'];

    $update_sql = "UPDATE users SET 
                   last_name = ?, 
                   first_name = ?, 
                   middle_name = ?, 
                   course_level = ?, 
                   email = ?, 
                   course = ?, 
                   address = ? 
                   WHERE id_number = ?";
                   
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssss", 
        $last_name, 
        $first_name, 
        $middle_name, 
        $course_level, 
        $email, 
        $course, 
        $address, 
        $student_id
    );
    
    if ($stmt->execute()) {
        echo "<script>alert('Student information updated successfully!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    } else {
        echo "<script>alert('Error updating student: " . $stmt->error . "');</script>";
    }
}

// Fetch student records with pagination
$records_per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Get total number of records
$total_records_query = "SELECT COUNT(*) as count FROM users";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['count'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch student records
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

        .dark body {
        background-color: #1a1a1a;
        color: #ffffff;
    }

    .dark .glass-morphism {
        background: rgba(31, 41, 55, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .dark .nav-container {
        @apply bg-gradient-to-r from-gray-800 to-gray-900;
    }

    .dark table thead {
        @apply bg-gray-800 text-gray-200;
    }

    .dark table tbody {
        @apply bg-gray-900 text-gray-300;
    }

    .dark table tbody tr:hover {
        @apply bg-gray-800;
    }

    .dark input, 
    .dark select, 
    .dark textarea {
        @apply bg-gray-800 border-gray-700 text-white;
    }

    .dark .modal-content {
        @apply bg-gray-900 text-white;
    }

    /* Theme transition */
    * {
        transition-property: background-color, border-color, color;
        transition-duration: 200ms;
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
                    <div class="flex items-center space-x-4">
                        <!-- Theme Toggle Button -->
                        <button id="themeToggle" class="px-4 py-2 rounded-lg border-2 border-white/80 hover:bg-white hover:text-indigo-600 transition-all duration-200">
                            <i class="fas fa-moon dark:hidden"></i>
                            <i class="fas fa-sun hidden dark:inline"></i>
                        </button>
                        
                        <!-- Logout Button -->
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
            
            <div class="mb-6 flex justify-between items-center">
                <div class="flex space-x-4">
                <button onclick="openModal()" class="bg-gray-900 text-white px-4 py-2 rounded-md shadow-sm hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-user-plus mr-2"></i>Add Student
                </button>
                <form method="post" class="inline" onsubmit="return confirmResetAll()">
                    <input type="hidden" name="reset_sessions" value="1">
                    <button type="submit" class="border border-gray-300 bg-white text-gray-700 font-medium py-2 px-4 rounded-md hover:bg-gray-50">
                        <i class="fas fa-redo mr-2"></i>Reset All Sessions
                    </button>
                </form>
                </div>
            </div>

            <!-- Table Content Here -->
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Middle Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions Left</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['id_number']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['first_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['middle_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['last_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($student['sessions_left']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        <!-- Reset Sessions Button -->
                                        <form method="post" class="inline" onsubmit="return confirmIndividualReset('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                            <input type="hidden" name="reset_individual_session" value="1">
                                            <button type="submit" class="bg-gray-900 hover:bg-gray-700 text-white font-medium py-1 px-3 rounded-md text-xs flex items-center">
                                                <i class="fas fa-redo mr-1"></i>Reset
                                            </button>
                                        </form>

                                        <!-- Edit Button -->
                                        <button onclick='openEditModal(<?php echo json_encode($student); ?>)' 
                                                class="border border-gray-300 bg-white text-gray-700 font-medium py-1 px-3 rounded-md text-xs flex items-center hover:bg-gray-50">
                                            <i class="fas fa-edit mr-1"></i>Edit
                                        </button>

                                        <!-- Delete Button -->
                                        <form method="post" class="inline" onsubmit="return confirmDelete('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                            <input type="hidden" name="delete_student" value="1">
                                            <button type="submit" class="border border-gray-300 bg-white text-red-600 font-medium py-1 px-3 rounded-md text-xs flex items-center hover:bg-gray-50 hover:text-red-700">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                No student records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination Controls -->
            <!-- (Keep your existing pagination code) -->
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
    <div id="registrationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 dark:text-white">
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
                        <button type="button" onclick="closeModal()" class="border border-gray-300 bg-white text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-gray-700">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800 dark:text-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Student Information</h3>
                <form method="post" class="space-y-4" id="editForm" onsubmit="return confirmEdit()">
                    <input type="hidden" name="update_student" value="1">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">ID Number</label>
                        <input type="text" id="edit_id_number" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Level</label>
                        <select name="course_level" id="edit_course_level" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course</label>
                        <select name="course" id="edit_course" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" id="edit_email" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea name="address" id="edit_address" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="border border-gray-300 bg-white text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="bg-gray-900 text-white px-4 py-2 rounded-md hover:bg-gray-700">Update</button>
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
        
        function confirmIndividualReset(studentId) {
            return confirm('Are you sure you want to reset sessions to 30 for student ID: ' + studentId + '?');
        }

    function openEditModal(student) {
        document.getElementById('edit_student_id').value = student.id_number;
        document.getElementById('edit_id_number').value = student.id_number;
        document.getElementById('edit_last_name').value = student.last_name;
        document.getElementById('edit_first_name').value = student.first_name;
        document.getElementById('edit_middle_name').value = student.middle_name || '';
        document.getElementById('edit_course_level').value = student.course_level;
        document.getElementById('edit_course').value = student.course;
        document.getElementById('edit_email').value = student.email;
        document.getElementById('edit_address').value = student.address;
        
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmEdit() {
        return confirm('Are you sure you want to update this student\'s information?');
    }

    function confirmDelete(studentId) {
        return confirm('Are you sure you want to delete this student? This action cannot be undone.');
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const registrationModal = document.getElementById('registrationModal');
        const editModal = document.getElementById('editModal');
        
        if (event.target === registrationModal) {
            closeModal();
        }
        if (event.target === editModal) {
            closeEditModal();
        }
    }

    // Handle escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
            closeEditModal();
        }
    });

    // Theme handling
    function initTheme() {
        // Check for saved theme preference or default to system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    // Theme toggle function
    function toggleTheme() {
        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
        }
    }

    // Update datetime function
    function updateDateTime() {
        const now = new Date();
        const formatted = now.getUTCFullYear() + '-' + 
                         String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                         String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                         String(now.getUTCHours()).padStart(2, '0') + ':' + 
                         String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                         String(now.getUTCSeconds()).padStart(2, '0');
        document.getElementById('currentDateTime').textContent = formatted;
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme
        initTheme();

        // Add theme toggle event listener
        document.getElementById('themeToggle').addEventListener('click', toggleTheme);

        // Update datetime every second
        setInterval(updateDateTime, 1000);
        updateDateTime(); // Initial update

        // Your existing event listeners...
    });

    // Update modal handling for dark mode
    function openModal() {
        document.getElementById('registrationModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('registrationModal').classList.add('hidden');
    }

    function openEditModal(student) {
        // Your existing openEditModal code...
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Add dark mode classes to modals
    document.querySelectorAll('.modal-content').forEach(modal => {
        modal.classList.add('dark:bg-gray-900', 'dark:text-white');
    });
    
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    dark: {
                        bg: '#1a1a1a',
                        surface: '#2d2d2d',
                        primary: '#0d1117',
                        secondary: '#161b22'
                    }
                }
            }
        }
    }
</script>
</body>
</html>
