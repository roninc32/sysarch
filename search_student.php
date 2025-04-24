<?php
include 'config.php';

// Handle search request via GET
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // Sanitize user input
    $search = $conn->real_escape_string($search);

    // First check if student exists
    $sql = "SELECT u.*, CASE WHEN a.student_id IS NOT NULL THEN 1 ELSE 0 END as has_active_sitin 
            FROM users u 
            LEFT JOIN active_sit_ins a ON u.id_number = a.student_id 
            WHERE u.id_number=? OR CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) LIKE ?";
    $stmt = $conn->prepare($sql);

    // Prepare the LIKE clause for searching
    $search_term = "%$search%";
    $stmt->bind_param("ss", $search, $search_term);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare response array
    $response = ['success' => false];

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Set the response to success and include the student details
        $response['success'] = true;
        $response['student'] = [
            'id_number' => $row['id_number'],
            'name' => $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'],
            'purpose' => 'C programming', // Default purpose (You can adjust this logic later)
            'lab' => '524', // Default lab (You can adjust this logic later)
            'sessions_left' => $row['sessions_left'],
            'has_active_sitin' => $row['has_active_sitin']
        ];
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Return the response as JSON for the search
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // Stop execution to avoid any further HTML output
}

// Handle sit-in activity POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $id_number = $_POST['id_number'];
    $name = $_POST['name'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $sessions_left = $_POST['sessions_left'] - 1;
    $login_time = date('H:i:s');
    $current_date = date('Y-m-d');

    // Check for active sit-in first
    $check_sql = "SELECT COUNT(*) as count FROM active_sit_ins WHERE student_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $id_number);
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
    $stmt_update->bind_param("is", $sessions_left, $id_number);
    $stmt_update->execute();

    // Insert into active_sit_ins table
    $sql_insert = "INSERT INTO active_sit_ins (student_id, name, sit_in_purpose, lab_number, login_time, date) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssss", $id_number, $name, $purpose, $lab, $login_time, $current_date);
    
    if ($stmt_insert->execute()) {
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
            --input-bg: #ffffff;
            --input-border: #d1d5db;
            --input-text: #111827;
            --input-placeholder: #9ca3af;
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
            --input-bg: #374151;
            --input-border: #4b5563;
            --input-text: #f9fafb;
            --input-placeholder: #9ca3af;
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
            border: 1px solid var(--input-border);
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
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
        }

        .btn-primary:hover {
            background-color: var(--button-hover);
        }
        
        /* Input styling */
        input, select, textarea {
            background-color: var(--input-bg);
            color: var(--input-text);
            border-color: var(--input-border);
            border-radius: 0.375rem;
        }
        
        input::placeholder {
            color: var(--input-placeholder);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--button-primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
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

        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Background styling */
        .bg-image {
            background-image: url('assets/images/bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .dark .bg-image {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/bg.jpg');
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-image">
    <!-- Navigation Bar - Updated to match dashboard -->
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
                            <a href="sit_in_records.php"
                                class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                <i class="fas fa-clipboard-list mr-2"></i> Sit-in Records
                            </a>
                            <a href="search_student.php"
                                class="nav-link text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                <i class="fas fa-search mr-2"></i> Search
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
                    <a href="admin_logout.php" class="btn-primary">
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
                <a href="sit_in_records.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list mr-2"></i> Sit-in Records
                </a>
                <a href="search_student.php"
                    class="nav-link block <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search mr-2"></i> Search
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
            .then(response => response.json())
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
                                                    <option value="Mac Laboratory">Mac Laboratory</option>
                                                </select>
                                            </div>
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