<?php
include 'config.php';

// Check if active_sit_ins table exists and create if not
$table_check = $conn->query("SHOW TABLES LIKE 'active_sit_ins'");
if ($table_check->num_rows == 0) {
    // Create the active_sit_ins table
    $create_table_sql = "CREATE TABLE `active_sit_ins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `sit_in_purpose` text NOT NULL,
        `lab_number` varchar(50) NOT NULL,
        `pc_number` varchar(20) DEFAULT NULL,
        `login_time` time NOT NULL,
        `date` date NOT NULL,
        `status` varchar(20) DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($create_table_sql) === TRUE) {
        // Success message if needed
    } else {
        // Error handling
        echo "<div class='bg-red-100 p-4 mb-4'>Error creating table: " . $conn->error . "</div>";
    }
}

// Add sessions_left column to users table if it doesn't exist
$column_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'sessions_left'");
if ($column_check->num_rows == 0) {
    $alter_table_sql = "ALTER TABLE `users` ADD COLUMN `sessions_left` int(11) NOT NULL DEFAULT 30";
    $conn->query($alter_table_sql);
}

// Handle search request via GET
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // Sanitize user input
    $search = $conn->real_escape_string($search);
    
    try {
        // First check if users table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'users'");
        if ($table_check->num_rows == 0) {
            // Users table doesn't exist
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false, 
                'message' => 'Database tables not set up properly. Please contact administrator.',
                'error_code' => 'table_not_found'
            ]);
            exit();
        }
        
        // For debugging purposes
        error_log("Search term: " . $search);
        
        // Check if active_sit_ins table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'active_sit_ins'");
        $has_active_sit_ins = ($table_check->num_rows > 0);
        
        // Based on the database dump, we see that active_sit_ins uses student_id, not id_number
        if ($has_active_sit_ins) {
            $sql = "SELECT u.*, CASE WHEN a.student_id IS NOT NULL THEN 1 ELSE 0 END as has_active_sitin 
                    FROM users u 
                    LEFT JOIN active_sit_ins a ON u.id_number = a.student_id
                    WHERE u.id_number = ? OR CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) LIKE ?";
        } else {
            $sql = "SELECT u.*, 0 as has_active_sitin 
                    FROM users u 
                    WHERE u.id_number = ? OR CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) LIKE ?";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        // For ID numbers, use exact match; for names, use LIKE with wildcards
        $name_search_term = "%$search%";
        $stmt->bind_param("ss", $search, $name_search_term);

        // Execute the query
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();

        // Prepare response array
        $response = ['success' => false];

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Set the response to success and include the student details
            $response['success'] = true;
            $response['student'] = [
                'id_number' => $row['id_number'],
                'name' => $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'],
                'purpose' => 'C programming', // Default purpose
                'lab' => '524', // Default lab
                'sessions_left' => $row['sessions_left'] ?? 30, // Default to 30 if not set
                'has_active_sitin' => $row['has_active_sitin']
            ];
            
            // Log found student for debugging
            error_log("Found student: " . $row['id_number'] . " - " . $row['first_name'] . " " . $row['last_name']);
        } else {
            // For debugging, let's check what's in the users table
            $debug_query = "SELECT id_number, first_name, last_name FROM users";
            $debug_result = $conn->query($debug_query);
            
            error_log("No student found for search: '$search'. Available users:");
            while ($debug_row = $debug_result->fetch_assoc()) {
                error_log("ID: {$debug_row['id_number']}, Name: {$debug_row['first_name']} {$debug_row['last_name']}");
            }
            
            $response['success'] = false;
            $response['message'] = "No student found matching '$search'. Please check the ID or name and try again.";
        }

        // Close statement
        $stmt->close();
        
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'message' => "Database error: " . $e->getMessage(),
            'error_code' => 'database_error'
        ];
        
        // Log the error
        error_log("Search error: " . $e->getMessage());
    }

    // Close connection and return response
    $conn->close();
    
    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // Stop execution to avoid any further HTML output
}

// Handle sit-in activity POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $student_id = $_POST['id_number'];
    $name = $_POST['name'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $pc_number = $_POST['pc_number'] ?? null; // Optional PC number
    $sessions_left = $_POST['sessions_left'] - 1;
    $login_time = date('H:i:s');
    $current_date = date('Y-m-d');

    // Check for active sit-in first - match the database structure fields
    $check_sql = "SELECT COUNT(*) as count FROM active_sit_ins WHERE student_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $active_count = $check_result->fetch_assoc()['count'];
    
    if ($active_count > 0) {
        echo "<script>alert('This student already has an active sit-in session!'); window.location.href='search_student.php';</script>";
        exit();
    }

    // Update sessions_left in users table
    $sql_update_sessions = "UPDATE users SET sessions_left = ? WHERE id_number = ?";
    $stmt_update = $conn->prepare($sql_update_sessions);
    $stmt_update->bind_param("is", $sessions_left, $student_id);
    $stmt_update->execute();

    // Insert into active_sit_ins table matching the actual database structure
    $sql_insert = "INSERT INTO active_sit_ins (student_id, name, sit_in_purpose, lab_number, login_time, date) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssss", $student_id, $name, $purpose, $lab, $login_time, $current_date);
    
    if ($stmt_insert->execute()) {
        // Also record in reservations table for later feedback
        $reservation_sql = "INSERT INTO reservations (id_number, name, sit_in_purpose, lab_number, login_time, date) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $reservation_stmt = $conn->prepare($reservation_sql);
        $reservation_stmt->bind_param("ssssss", $student_id, $name, $purpose, $lab, $login_time, $current_date);
        $reservation_stmt->execute();
        
        header("Location: sit_in_records.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt_update->close();
    $stmt_insert->close();
}
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        /* Dark mode variables - matching admin dashboard */
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
            --chart-bg: #ffffff;
            --chart-text: #111827;
            --announcement-bg: #ffffff;
            --announcement-text: #111827;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --border-color: #e5e7eb;
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
            --chart-bg: #1f2937;
            --chart-text: #f9fafb;
            --announcement-bg: #1f2937;
            --announcement-text: #f9fafb;
            --accent-blue: #60a5fa;
            --accent-green: #34d399;
            --accent-yellow: #fbbf24;
            --border-color: #374151;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            line-height: 1.5;
        }

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
            color: var(--button-text);
            font-weight: 600;
        }

        .card {
            background-color: var(--card-bg);
            transition: transform 0.3s, box-shadow 0.3s;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px var(--shadow-color);
        }

        .card-header {
            background-color: var(--card-header);
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary {
            background-color: var(--button-primary);
            color: var(--button-text);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: var(--button-hover);
        }
        
        /* Improved text styles for better readability */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0.5rem;
        }
        
        p, li {
            line-height: 1.6;
        }
        
        /* Enhanced contrast for content */
        .text-enhanced {
            font-weight: 500;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        /* Table styling */
        table {
            border-color: var(--border-color);
        }
        
        table thead {
            background-color: var(--table-header-bg);
        }
        
        table tbody tr {
            background-color: var(--card-bg);
        }
        
        table tbody tr:hover {
            background-color: var(--table-row-hover);
        }

        /* Star rating display */
        .star-display {
            color: #ccc;
        }

        .star-filled {
            color: #ffb700;
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

        /* Rating category colors */
        .rating-excellent {
            background-color: rgba(16, 185, 129, 0.15);
            color: #047857;
        }

        .dark .rating-excellent {
            background-color: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
        
        .rating-good {
            background-color: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .dark .rating-good {
            background-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }
        
        .rating-average {
            background-color: rgba(245, 158, 11, 0.15);
            color: #b45309;
        }

        .dark .rating-average {
            background-color: rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }
        
        .rating-poor {
            background-color: rgba(239, 68, 68, 0.15);
            color: #b91c1c;
        }

        .dark .rating-poor {
            background-color: rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Animation for hover effect */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .hover-pulse:hover {
            animation: pulse 1s infinite;
        }

        /* Notifications */
        .notification {
            transition: opacity 0.5s ease-out;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar - Updated to match admin dashboard -->
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
                    <a href="admin_logout.php" 
                       class="btn-primary flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
        <div class="sm:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="admin_dashboard.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home mr-2"></i> Dashboard
                </a>
                <a href="student_record.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users mr-2"></i> Students
                </a>
                <a href="admin_reservation.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check mr-2"></i> Reservations
                </a>
                <a href="sit_in_records.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list mr-2"></i> Sit-in Records
                </a>
                <a href="search_student.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search mr-2"></i> Search
                </a>
                <a href="feedback.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <i class="fas fa-comments mr-2"></i> Feedback
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex-grow">
        <div class="card shadow-lg animate-fadeIn">
            <div class="card-header flex items-center">
                <i class="fas fa-search mr-3 text-blue-500 dark:text-blue-400"></i>
                <h1 class="text-xl font-bold">Search Student</h1>
            </div>
            <div class="p-6">
                <p class="text-gray-600 dark:text-gray-400 mb-6">Enter student ID or name to search for student records</p>
                <form id="searchForm" class="mb-8">
                    <div class="flex flex-col md:flex-row gap-4">
                        <input type="text" 
                            id="searchInput" 
                            name="search" 
                            placeholder="Enter student ID or name" 
                            required 
                            class="flex-1 p-3 border rounded-lg shadow-sm"
                            autocomplete="off">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search mr-2"></i>Search
                        </button>
                    </div>
                </form>
                <div id="result"></div>
            </div>
        </div>
    </div>

    <!-- Footer --> 
    <footer class="mt-auto py-4 border-t border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> Admin Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });

    // Dark mode toggle functionality
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;

    // Check for saved theme preference or use system preference
    const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const savedTheme = localStorage.getItem('theme');

    if (savedTheme === 'dark' || (!savedTheme && darkModeMediaQuery.matches)) {
        html.classList.add('dark');
        darkModeToggle.checked = true;
    }

    // Toggle theme when button is clicked
    darkModeToggle.addEventListener('change', function() {
        if (this.checked) {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    });

    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('searchInput').value;

        // Show loading state
        const resultContainer = document.getElementById('result');
        resultContainer.innerHTML = `
            <div class="flex justify-center items-center p-8">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
        `;

        fetch(`?search=${encodeURIComponent(searchInput)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const student = data.student;
                    if (student.has_active_sitin) {
                        resultContainer.innerHTML = `
                            <div class="card shadow-md">
                                <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 px-4 py-3 mb-4">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span>This student already has an active sit-in session!</span>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <h2 class="text-xl font-bold mb-6 pb-2 border-b border-gray-200 dark:border-gray-700">Student Details</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">ID Number</p>
                                            <p class="font-semibold">${student.id_number}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Name</p>
                                            <p class="font-semibold">${student.name}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Sessions Left</p>
                                            <p class="font-semibold">${student.sessions_left}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        resultContainer.innerHTML = `
                            <div class="card shadow-md">
                                <div class="p-6">
                                    <h2 class="text-xl font-bold mb-6 pb-2 border-b border-gray-200 dark:border-gray-700">Student Details</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">ID Number</p>
                                            <p class="font-semibold">${student.id_number}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Name</p>
                                            <p class="font-semibold">${student.name}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Sessions Left</p>
                                            <p class="font-semibold">${student.sessions_left}</p>
                                        </div>
                                    </div>

                                    <h3 class="text-lg font-bold mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">Log Sit-in Activity</h3>
                                    <form method="post" action="search_student.php" class="space-y-4" onsubmit="return logSitInActivity()">
                                        <input type="hidden" name="id_number" value="${student.id_number}">
                                        <input type="hidden" name="name" value="${student.name}">
                                        <input type="hidden" name="sessions_left" value="${student.sessions_left}">
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div>
                                                <label class="block text-gray-500 dark:text-gray-400 text-sm mb-2">Purpose</label>
                                                <select name="purpose" required class="w-full p-3 border rounded-lg shadow-sm">
                                                    <option value="C programming">C programming</option>
                                                    <option value="Java programming">Java programming</option>
                                                    <option value="C# programming">C# programming</option>
                                                    <option value="PHP programming">PHP programming</option>
                                                    <option value="ASP.NET programming">ASP.NET programming</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-gray-500 dark:text-gray-400 text-sm mb-2">Lab</label>
                                                <select name="lab" required class="w-full p-3 border rounded-lg shadow-sm">
                                                    <option value="524">524</option>
                                                    <option value="526">526</option>
                                                    <option value="528">528</option>
                                                    <option value="530">530</option>
                                                    <option value="MAC Laboratory">Mac Laboratory</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-gray-500 dark:text-gray-400 text-sm mb-2">PC Number (Optional)</label>
                                            <input type="text" name="pc_number" placeholder="Enter PC number" class="w-full p-3 border rounded-lg shadow-sm">
                                        </div>
                                        
                                        <div class="flex justify-end mt-6">
                                            <button type="submit" name="handle_sit_in" class="btn-primary">
                                                <i class="fas fa-sign-in-alt mr-2"></i>Start Sit-in Session
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        `;
                    }
                } else {
                    resultContainer.innerHTML = `
                        <div class="bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 p-4 rounded-md">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>No student found matching "${searchInput}". Please check the ID or name and try again.</span>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultContainer.innerHTML = `
                    <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-md">
                        <div class="flex items-center">
                            <i class="fas fa-times-circle mr-2"></i>
                            <span>An error occurred while searching. Please try again later.</span>
                        </div>
                    </div>
                `;
            });
    });

    function logSitInActivity() {
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-md shadow-lg z-50 animate-fadeIn';
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>Sit-in activity logged successfully!</span>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Remove notification after delay
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => document.body.removeChild(notification), 500);
        }, 3000);
        
        return true;
    }
</script>

</body>
</html>