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

// Handle point granting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grant_points'])) {
    $student_id = $_POST['student_id'];
    $points_to_add = (int)$_POST['points_to_add'];
    
    if ($points_to_add <= 0) {
        echo "<script>alert('Please enter a valid number of points.');</script>";
    } else {
        $update_sql = "UPDATE users SET points = points + ? WHERE id_number = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("is", $points_to_add, $student_id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Points added successfully!');</script>";
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error adding points: " . $stmt->error . "');</script>";
        }
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
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dark mode variables - matching dashboard */
        :root {
            --bg-primary: #f0f9ff;
            --bg-secondary: #dbeafe;
            --text-primary: #111827;
            --text-secondary: #374151;
            --card-bg: #ffffff;
            --card-header: #bfdbfe;
            --nav-bg: #ffffff;
            --nav-text: #111827;
            --nav-hover-bg: #3b82f6;
            --nav-hover-text: #ffffff;
            --button-primary: #3b82f6;
            --button-hover: #2563eb;
            --button-text: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --table-header-bg: #f3f4f6;
            --table-row-bg: #ffffff;
            --table-row-hover: #f9fafb;
            --table-border: #e5e7eb;
            --table-text: #111827;
            --input-bg: #ffffff;
            --input-text: #111827;
            --input-border: #d1d5db;
            --modal-bg: #ffffff;
            --modal-text: #111827;
        }

        .dark {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --card-bg: #1f2937;
            --card-header: #2d3748;
            --nav-bg: #111827;
            --nav-text: #f9fafb;
            --nav-hover-bg: #3b82f6;
            --nav-hover-text: #ffffff;
            --button-primary: #3b82f6;
            --button-hover: #60a5fa;
            --button-text: #f9fafb;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --table-header-bg: #374151;
            --table-row-bg: #1f2937;
            --table-row-hover: #2d3748;
            --table-border: #4b5563;
            --table-text: #f9fafb;
            --input-bg: #374151;
            --input-text: #f9fafb;
            --input-border: #4b5563;
            --modal-bg: #1f2937;
            --modal-text: #f9fafb;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            line-height: 1.5;
        }

        /* Navigation styling */
        nav {
            background-color: var(--nav-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .nav-link {
            color: var(--nav-text);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .nav-link:hover {
            background-color: var(--nav-hover-bg);
            color: var(--nav-hover-text);
        }

        .nav-link.active {
            background-color: var(--button-primary);
            color: var (--button-text);
            font-weight: 600;
        }

        /* Card and button styling */
        .card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            border: 1px solid var(--table-border);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px var(--shadow-color);
        }

        .card-header {
            background-color: var(--card-header);
            padding: 1rem;
        }

        .btn-primary {
            background-color: var(--button-primary);
            color: var(--button-text);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--button-hover);
        }

        /* Table styling */
        table {
            border-color: var(--table-border);
        }

        table thead {
            background-color: var(--table-header-bg);
        }

        table thead th {
            color: var(--text-primary);
            font-weight: 600;
        }

        table tbody tr {
            background-color: var(--table-row-bg);
            color: var(--table-text);
        }

        table tbody tr:hover {
            background-color: var(--table-row-hover);
        }

        /* Form input styling */
        input, select, textarea {
            background-color: var(--input-bg);
            color: var(--input-text);
            border-color: var(--input-border);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--button-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        /* Modal styling */
        .modal-content {
            background-color: var(--modal-bg);
            color: var(--modal-text);
            border: 1px solid var(--table-border);
        }

        /* Toggle switch styling */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 26px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #3b82f6;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Background image handling */
        .bg-image {
            background-image: url('assets/images/bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .dark .bg-image {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/bg.jpg');
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-image">
    <header class="w-full top-0 z-50">
        <nav class="sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative flex items-center justify-between h-16">
                    <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                        <button type="button" id="mobile-menu-button"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                            <span class="sr-only">Open main menu</span>
                            <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                        <div class="flex-shrink-0 flex items-center">
                            <span class="text-xl font-bold hidden lg:block">Admin Portal</span>
                        </div>
                        <div class="hidden sm:block sm:ml-6">
                            <div class="flex space-x-4">
                                <a href="admin_dashboard.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-home mr-2"></i> Dashboard
                                </a>
                                <a href="student_record.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-users mr-2"></i> Students
                                </a>
                                <a href="admin_reservation.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-calendar-check mr-2"></i> Reservations
                                </a>
                                <a href="sit_in_records.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-clipboard-list mr-2"></i> Sit-in Records
                                </a>
                                <a href="search_student.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-search mr-2"></i> Search
                                </a>
                                <a href="feedback.php"
                                    class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-comments mr-2"></i> Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0 space-x-3">
                        <!-- Dark Mode Toggle -->
                        <div class="flex items-center mr-4">
                            <span class="mr-2 text-sm"><i class="fas fa-sun"></i></span>
                            <label class="toggle-switch">
                                <input type="checkbox" id="darkModeToggle">
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="ml-2 text-sm"><i class="fas fa-moon"></i></span>
                        </div>
                        
                        <!-- Admin Logout -->
                        <a href="admin_logout.php" class="btn-primary flex items-center">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="sm:hidden hidden" id="mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="admin_dashboard.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="student_record.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users mr-2"></i>Students
                    </a>
                    <a href="admin_reservation.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check mr-2"></i>Reservations
                    </a>
                    <a href="sit_in_records.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list mr-2"></i>Sit-in Records
                    </a>
                    <a href="search_student.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                        <i class="fas fa-search mr-2"></i>Search
                    </a>
                    <a href="feedback.php" class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comments mr-2"></i>Feedback
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto p-6 lg:p-8 flex-grow">
        <div class="card shadow-lg p-6 lg:p-8">
            <h1 class="text-3xl font-bold text-center mb-8 flex items-center justify-center">
                <i class="fas fa-users mr-3 text-blue-500 dark:text-blue-400"></i>Student Records
            </h1>
            
            <div class="mb-6 flex justify-between items-center">
                <div class="flex space-x-4">
                    <button onclick="openModal()" class="btn-primary flex items-center">
                        <i class="fas fa-user-plus mr-2"></i>Add Student
                    </button>
                    <form method="post" class="inline" onsubmit="return confirmResetAll()">
                        <input type="hidden" name="reset_sessions" value="1">
                        <button type="submit" class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 font-medium py-2 px-4 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-redo mr-2"></i>Reset All Sessions
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Content Here -->
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">First Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Middle Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Last Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Sessions Left</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Points</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php if (!empty($students)): ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['id_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['first_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['middle_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['last_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($student['sessions_left'] <= 5): ?>
                                            <span class="text-red-600 dark:text-red-400"><?php echo htmlspecialchars($student['sessions_left']); ?></span>
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($student['sessions_left']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <span class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 py-1 px-2 rounded-full">
                                            <?php echo isset($student['points']) ? htmlspecialchars($student['points']) : '0'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="flex items-center space-x-2">
                                            <!-- Reset Sessions Button -->
                                            <form method="post" class="inline" onsubmit="return confirmIndividualReset('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                                <input type="hidden" name="reset_individual_session" value="1">
                                                <button type="submit" class="btn-primary py-1 px-2 rounded-md text-xs">
                                                    <i class="fas fa-redo mr-1"></i>Reset
                                                </button>
                                            </form>

                                            <!-- Grant Points Button -->
                                            <button onclick='openPointsModal("<?php echo htmlspecialchars($student['id_number']); ?>", "<?php echo htmlspecialchars($student['first_name']); ?> <?php echo htmlspecialchars($student['last_name']); ?>")' 
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-2 rounded-md text-xs">
                                                <i class="fas fa-gift mr-1"></i>Points
                                            </button>

                                            <!-- Edit Button -->
                                            <button onclick='openEditModal(<?php echo json_encode($student); ?>)' 
                                                    class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 py-1 px-2 rounded-md text-xs hover:bg-gray-50 dark:hover:bg-gray-600">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>

                                            <!-- Delete Button -->
                                            <form method="post" class="inline" onsubmit="return confirmDelete('<?php echo htmlspecialchars($student['id_number']); ?>')">
                                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id_number']); ?>">
                                                <input type="hidden" name="delete_student" value="1">
                                                <button type="submit" class="border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 py-1 px-2 rounded-md text-xs hover:bg-gray-50 dark:hover:bg-gray-600">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    No student records found.
                                </td>
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
                            <a href="?page=<?php echo ($page - 1); ?>" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php
                        // Calculate range of page numbers to show
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        // Show first page if we're not starting at 1
                        if ($start_page > 1) {
                            echo '<a href="?page=1" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">1</a>';
                            if ($start_page > 2) {
                                echo '<span class="px-3 py-1">...</span>';
                            }
                        }

                        // Show page numbers
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            if ($i == $page) {
                                echo '<span class="px-3 py-1 bg-blue-500 text-white rounded-md">' . $i . '</span>';
                            } else {
                                echo '<a href="?page=' . $i . '" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">' . $i . '</a>';
                            }
                        }

                        // Show last page if we're not ending at total_pages
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) {
                                echo '<span class="px-3 py-1">...</span>';
                            }
                            echo '<a href="?page=' . $total_pages . '" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">' . $total_pages . '</a>';
                        }
                        ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>" class="px-3 py-1 bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Next
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>

            <!-- Records info display -->
            <div class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
                Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> to 
                <?php echo min($page * $records_per_page, $total_records); ?> of 
                <?php echo $total_records; ?> records
            </div>
        </div>
    </main>

    <footer class="py-4 text-center text-white mt-8">
        <p>&copy; <?php echo date("Y"); ?> Admin Portal. All rights reserved.</p>
    </footer>

    <!-- Registration Modal -->
    <div id="registrationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md modal-content">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700">Register New Student</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmRegistration()">
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" name="id_number" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" name="last_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" name="first_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Middle Name</label>
                        <input type="text" name="middle_name" class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course Level</label>
                        <select name="course_level" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course</label>
                        <select name="course" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea name="address" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary">Register</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md modal-content">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700">Edit Student Information</h3>
                <form method="post" class="space-y-4" id="editForm" onsubmit="return confirmEdit()">
                    <input type="hidden" name="update_student" value="1">
                    <input type="hidden" name="student_id" id="edit_student_id">
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">ID Number</label>
                        <input type="text" id="edit_id_number" disabled class="mt-1 block w-full px-3 py-2 border rounded-md bg-gray-100 dark:bg-gray-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Middle Name</label>
                        <input type="text" name="middle_name" id="edit_middle_name" class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course Level</label>
                        <select name="course_level" id="edit_course_level" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Course</label>
                        <select name="course" id="edit_course" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Computer Science">BSCS</option>
                            <option value="Information Technology">BSIT</option>
                            <option value="Software Engineering">BSSE</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <input type="email" name="email" id="edit_email" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Address</label>
                        <textarea name="address" id="edit_address" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Points Modal -->
    <div id="pointsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md modal-content">
            <div class="mt-3">
                <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200 dark:border-gray-700">Grant Points</h3>
                <form method="post" class="space-y-4" onsubmit="return confirmPointsGrant()">
                    <input type="hidden" name="grant_points" value="1">
                    <input type="hidden" name="student_id" id="points_student_id">
                    
                    <div class="mb-4">
                        <p class="text-sm">Granting points to: <strong id="student_name_display"></strong></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-1">Number of Points to Add</label>
                        <input type="number" name="points_to_add" min="1" required class="mt-1 block w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">3 points can be converted to 1 session by the student.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closePointsModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="btn-primary bg-yellow-500 hover:bg-yellow-600">Grant Points</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize theme handling
        function initTheme() {
            // Check for saved theme preference or default to system preference
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                document.getElementById('darkModeToggle').checked = true;
            } else {
                document.documentElement.classList.remove('dark');
                document.getElementById('darkModeToggle').checked = false;
            }
        }

        // Toggle dark mode
        function toggleTheme() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
        
        // Modal functions
        function openModal() {
            document.getElementById('registrationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeModal() {
            document.getElementById('registrationModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
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
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }
        
        // Points modal functions
        function openPointsModal(studentId, studentName) {
            document.getElementById('points_student_id').value = studentId;
            document.getElementById('student_name_display').textContent = studentName;
            document.getElementById('pointsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }

        function closePointsModal() {
            document.getElementById('pointsModal').classList.add('hidden');
            document.body.style.overflow = ''; // Allow scrolling
        }
        
        // Confirmation functions
        function confirmRegistration() {
            return confirm('Are you sure you want to register this student?');
        }
        
        function confirmIndividualReset(studentId) {
            return confirm('Are you sure you want to reset sessions to 30 for student ID: ' + studentId + '?');
        }
        
        function confirmResetAll() {
            return confirm('Are you sure you want to reset sessions to 30 for ALL students? This cannot be undone.');
        }

        function confirmEdit() {
            return confirm('Are you sure you want to update this student\'s information?');
        }

        function confirmDelete(studentId) {
            return confirm('Are you sure you want to delete this student? This action cannot be undone.');
        }
        
        function confirmPointsGrant() {
            return confirm('Are you sure you want to grant these points to the student?');
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize theme
            initTheme();
            
            // Add dark mode toggle listener
            document.getElementById('darkModeToggle').addEventListener('change', toggleTheme);
            
            // Mobile menu toggle
            document.getElementById('mobile-menu-button').addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            });
            
            // Close modals when clicking outside or pressing escape
            window.addEventListener('click', function(event) {
                const registrationModal = document.getElementById('registrationModal');
                const editModal = document.getElementById('editModal');
                const pointsModal = document.getElementById('pointsModal');
                
                if (event.target === registrationModal) {
                    closeModal();
                }
                
                if (event.target === editModal) {
                    closeEditModal();
                }
                
                if (event.target === pointsModal) {
                    closePointsModal();
                }
            });
            
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    closeModal();
                    closeEditModal();
                    closePointsModal();
                }
            });
        });
    </script>
</body>
</html>
