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
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --text-primary: #334155;
            --text-secondary: #64748b;
            --accent-color: #3b82f6;
            --accent-hover: #2563eb;
            --accent-light: #dbeafe;
            --sidebar-width: 280px;
            --header-height: 64px;
            --border-color: #e2e8f0;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-bg: #fff;
            --section-title-color: #94a3b8;
        }

        .dark {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent-light: #1e3a8a;
            --accent-hover: #60a5fa;
            --border-color: #334155;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4);
            --card-bg: #1e293b;
            --section-title-color: #64748b;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            transition: background-color 0.2s, color 0.2s;
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background-color: var(--card-bg);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-header {
            height: 70px;
            padding: 0 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: var(--card-bg);
            z-index: 10;
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: -0.01em;
            color: var(--text-primary);
        }
        
        .sidebar-content {
            flex: 1;
            padding: 16px 12px;
        }
        
        .sidebar-section {
            margin-bottom: 24px;
        }
        
        .section-title {
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            color: var(--section-title-color);
            letter-spacing: 0.05em;
            padding: 0 12px;
            margin-bottom: 8px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            border-radius: 8px;
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background-color: var(--bg-secondary);
        }
        
        .nav-item.active {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        
        .nav-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: var(--text-secondary);
        }
        
        .nav-item.active .nav-icon {
            color: var(--accent-color);
        }
        
        .user-section {
            padding: 16px;
            border-top: 1px solid var(--border-color);
            margin-top: auto;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .user-info:hover {
            background-color: var(--bg-secondary);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background-color: var(--accent-light);
            color: var(--accent-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        .user-details {
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .main-content {
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .topbar {
            height: 70px;
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            padding: 0 24px;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .page-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--text-primary);
        }
        
        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 8px 16px;
            width: 240px;
        }
        
        .search-input {
            border: none;
            background: none;
            color: var(--text-primary);
            flex: 1;
            outline: none;
            font-size: 14px;
        }
        
        .search-input::placeholder {
            color: var(--text-secondary);
        }
        
        .search-icon {
            color: var (--text-secondary);
            font-size: 14px;
            margin-right: 8px;
        }
        
        .theme-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 22px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--bg-secondary);
            transition: .4s;
            border-radius: 34px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--accent-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(22px);
        }
        
        .content-area {
            padding: 24px;
            flex: 1;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 90;
        }
        
        @media (max-width: 768px) {
            body {
                overflow-y: auto;
            }
            
            .sidebar {
                position: fixed;
                left: -280px;
                z-index: 100;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1);
            }
            
            .sidebar.open {
                left: 0;
            }
            
            .main-content {
                width: 100%;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar with categories -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="logo-text">SIT-IN Admin</div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <a href="admin_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Management</div>
                <a href="student_record.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
                    <span>Students</span>
                </a>
                <a href="admin_reservation.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                    <span>Reservations</span>
                </a>
                <a href="sit_in_records.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
                    <span>Sit-in Records</span>
                </a>
                <a href="search_student.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-search"></i></div>
                    <span>Search</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Features</div>
                <a href="schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>Schedules</span>
                </a>
                <a href="feedback.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-comments"></i></div>
                    <span>Feedback</span>
                </a>
                <a href="resources.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cube"></i></div>
                    <span>Resources</span>
                </a>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">A</div>
                <div class="user-details">
                    <div class="user-name">Admin User</div>
                    <div class="user-role">System Administrator</div>
                </div>
                <div>
                    <a href="admin_logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt text-gray-400 hover:text-red-500"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overlay" id="overlay"></div>
    
    <!-- Main content area -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Search Student</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="theme-toggle">
                    <i class="fas fa-sun"></i>
                    <label class="switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="slider"></span>
                    </label>
                    <i class="fas fa-moon"></i>
                </div>
            </div>
        </div>
        
        <div class="content-area">
            <div class="card">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold flex items-center">
                        <i class="fas fa-search mr-3 text-blue-500"></i>
                        Student Lookup
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Search for students by ID or name to manage sit-in sessions</p>
                </div>
                
                <div class="p-6">
                    <form id="searchForm" class="mb-6">
                        <div class="flex flex-col md:flex-row gap-4">
                            <input type="text" 
                                id="searchInput" 
                                name="search" 
                                placeholder="Enter student ID or name" 
                                required 
                                class="flex-1 p-3 border rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                autocomplete="off">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                        </div>
                    </form>
                    <div id="result"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').style.display = 
                document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
        });
        
        document.getElementById('overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('open');
            this.style.display = 'none';
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
                                <div class="card">
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
                                <div class="card">
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
                                                <button type="submit" name="handle_sit_in" class="btn btn-primary">
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