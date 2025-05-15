<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Handle feedback deletion
if (isset($_POST['delete_feedback']) && isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);
    
    // Prepare and execute the delete statement
    $delete_sql = "DELETE FROM feedback WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $feedback_id);
    
    if ($delete_stmt->execute()) {
        // Set success message
        $_SESSION['delete_success'] = "Feedback entry has been deleted successfully.";
    } else {
        // Set error message
        $_SESSION['delete_error'] = "Error deleting feedback: " . $conn->error;
    }
    
    // Redirect to refresh the page
    header("Location: feedback.php");
    exit();
}

// Get all feedback with reservation details
$sql = "SELECT f.*, r.lab_number, r.date, r.login_time, r.logout_time, r.sit_in_purpose, 
               r.id_number, r.name as student_name
        FROM feedback f
        INNER JOIN reservations r ON f.reservation_id = r.id
        ORDER BY f.created_at DESC";
$result = $conn->query($sql);

$feedback_list = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedback_list[] = $row;
    }
}

// Get statistics
$sql_stats = "SELECT 
    COUNT(*) as total_feedback,
    AVG(rating) as avg_rating,
    COUNT(CASE WHEN had_issues = 1 THEN 1 END) as issues_count,
    COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_feedback,
    COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_feedback
    FROM feedback";
$stats_result = $conn->query($sql_stats);
$stats = $stats_result->fetch_assoc();

// Get lab statistics
$sql_lab_stats = "SELECT 
    r.lab_number, 
    COUNT(f.id) as feedback_count, 
    AVG(f.rating) as avg_rating
    FROM feedback f
    JOIN reservations r ON f.reservation_id = r.id
    GROUP BY r.lab_number
    ORDER BY avg_rating DESC";
$lab_stats_result = $conn->query($sql_lab_stats);
$lab_stats = [];
if ($lab_stats_result->num_rows > 0) {
    while ($row = $lab_stats_result->fetch_assoc()) {
        $lab_stats[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback - Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold mb-6 flex items-center animate-fadeIn">
                <i class="fas fa-comments mr-3 text-blue-500"></i>
                <span>Student Feedback</span>
            </h1>
            
            <!-- Feedback Stats Cards - Improved visibility to match dashboard -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fadeIn">
                <!-- Total Feedback -->
                <div class="card shadow-lg">
                    <div class="card-header bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-comments mr-2 text-blue-500 dark:text-blue-400"></i>Total Feedback
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="stat-value text-blue-600 dark:text-blue-400"><?php echo $stats['total_feedback']; ?></p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">Total submissions</p>
                    </div>
                </div>
                
                <!-- Average Rating -->
                <div class="card shadow-lg">
                    <div class="card-header bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-star mr-2 text-green-500 dark:text-green-400"></i>Average Rating
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="stat-value text-green-600 dark:text-green-400"><?php echo number_format($stats['avg_rating'], 1); ?></p>
                        <div class="flex justify-center mt-2">
                            <?php
                            $avg_rating = round($stats['avg_rating'] * 2) / 2; // Round to nearest 0.5
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star text-yellow-400 mx-1"></i>';
                                } else if ($i - 0.5 == $avg_rating) {
                                    echo '<i class="fas fa-star-half-alt text-yellow-400 mx-1"></i>';
                                } else {
                                    echo '<i class="far fa-star text-yellow-400 mx-1"></i>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Positive Feedback -->
                <div class="card shadow-lg">
                    <div class="card-header bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-thumbs-up mr-2 text-indigo-500 dark:text-indigo-400"></i>Positive Feedback
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="stat-value text-indigo-600 dark:text-indigo-400"><?php echo $stats['positive_feedback']; ?></p>
                        <?php $positive_percent = $stats['total_feedback'] > 0 ? round(($stats['positive_feedback'] / $stats['total_feedback']) * 100) : 0; ?>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">
                            <?php echo $positive_percent; ?>% of total feedback
                        </p>
                    </div>
                </div>
                
                <!-- Technical Issues -->
                <div class="card shadow-lg">
                    <div class="card-header bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200">
                        <h3 class="text-lg font-semibold flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2 text-red-500 dark:text-red-400"></i>Technical Issues
                        </h3>
                    </div>
                    <div class="p-6 text-center">
                        <p class="stat-value text-red-600 dark:text-red-400"><?php echo $stats['issues_count']; ?></p>
                        <?php $issues_percent = $stats['total_feedback'] > 0 ? round(($stats['issues_count'] / $stats['total_feedback']) * 100) : 0; ?>
                        <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">
                            <?php echo $issues_percent; ?>% reported issues
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Table - Improved visibility -->
            <div class="card shadow-lg">
                <div class="card-header flex justify-between items-center">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="fas fa-list mr-2 text-blue-500 dark:text-blue-400"></i>Student Feedback List
                    </h2>
                    <div class="w-64">
                        <div class="relative">
                            <input type="text" id="searchInput" class="w-full px-4 py-2 pr-8 rounded-lg border border-gray-300 dark:border-gray-600 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" placeholder="Search feedback...">
                            <button type="button" class="absolute right-0 top-0 mt-2 mr-3 text-gray-400 dark:text-gray-300">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="feedbackTable">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Lab #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Session Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Rating
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Technical Issues
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Submitted
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (empty($feedback_list)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="far fa-comment-slash text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No feedback submissions yet</p>
                                            <p class="text-base text-gray-600 dark:text-gray-400">Student feedback will appear here</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($feedback_list as $feedback): ?>
                                    <tr class="feedback-item hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                                    <span class="font-medium text-blue-800 dark:text-blue-200">
                                                        <?php echo substr($feedback['student_name'], 0, 1); ?>
                                                    </span>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($feedback['student_name']); ?></p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($feedback['id_number']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-sm rounded-md bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 font-medium">
                                                Lab <?php echo htmlspecialchars($feedback['lab_number']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo date('M d, Y', strtotime($feedback['date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="text-sm font-medium mr-2 text-gray-700 dark:text-gray-300"><?php echo $feedback['rating']; ?></span>
                                                <div class="flex">
                                                    <?php
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo '<span class="' . ($i <= $feedback['rating'] ? 'star-filled' : 'star-display') . '"><i class="fas fa-star text-xs"></i></span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($feedback['had_issues']): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                                                <i class="fas fa-exclamation-circle mr-1"></i> Yes
                                            </span>
                                            <?php else: ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200">
                                                <i class="fas fa-check-circle mr-1"></i> No
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex space-x-2">
                                                <button class="view-details px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors" 
                                                    data-feedback='<?php echo htmlspecialchars(json_encode([
                                                        'id' => $feedback['id'],
                                                        'student_name' => $feedback['student_name'],
                                                        'id_number' => $feedback['id_number'],
                                                        'lab_number' => $feedback['lab_number'],
                                                        'date' => date('F d, Y', strtotime($feedback['date'])),
                                                        'purpose' => $feedback['sit_in_purpose'],
                                                        'rating' => $feedback['rating'],
                                                        'feedback_message' => $feedback['message'],
                                                        'had_technical_issues' => $feedback['had_issues'],
                                                        'issues_description' => $feedback['issues_description'],
                                                        'submission_date' => date('F d, Y', strtotime($feedback['created_at'])),
                                                        'login_time' => date('h:i A', strtotime($feedback['login_time'])),
                                                        'logout_time' => !empty($feedback['logout_time']) ? date('h:i A', strtotime($feedback['logout_time'])) : 'N/A'
                                                    ])); ?>'>
                                                    <i class="fas fa-eye mr-1"></i> View
                                                </button>
                                                
                                                <button class="delete-feedback px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800 transition-colors" 
                                                    data-id="<?php echo $feedback['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($feedback['student_name']); ?>">
                                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Enhanced feedback details modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center border-b p-4 sticky top-0 bg-white dark:bg-gray-800 z-10">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Feedback Details</h3>
                <button class="close-modal text-gray-400 hover:text-gray-500" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="flex items-center mb-4">
                        <div class="h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-600 dark:text-blue-300 mr-4">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white" id="modal-student-name"></h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400" id="modal-student-id"></p>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Lab Session</h5>
                        <p class="text-base font-semibold text-gray-900 dark:text-white" id="modal-lab-number"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Session Date</h5>
                        <p class="text-base font-semibold text-gray-900 dark:text-white" id="modal-date"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Session Time</h5>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            <span id="modal-login-time"></span> - <span id="modal-logout-time"></span>
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-1">Purpose</h5>
                        <p class="text-base font-semibold text-gray-900 dark:text-white" id="modal-purpose"></p>
                    </div>
                </div>
                
                <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg">
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Rating</h5>
                    <div class="flex items-center">
                        <div class="text-2xl font-bold mr-3 text-yellow-600 dark:text-yellow-400" id="modal-rating"></div>
                        <div class="flex" id="modal-stars">
                            <!-- Stars will be populated with JS -->
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Feedback Comments</h5>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <p class="text-sm text-gray-800 dark:text-gray-200" id="modal-message">No comments provided</p>
                    </div>
                </div>
                
                <div id="issues-section" class="mb-6">
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase mb-2">Technical Issues</h5>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                        <div id="no-issues-text" class="text-sm text-gray-800 dark:text-gray-200">No technical issues reported</div>
                        <div id="issues-text" class="hidden">
                            <p class="text-sm font-medium mb-2 text-red-600 dark:text-red-400">
                                <i class="fas fa-exclamation-circle mr-1"></i> The student reported technical issues
                            </p>
                            <p class="text-sm text-gray-800 dark:text-gray-200" id="modal-issues-description">No additional details provided</p>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-4 text-right">
                    <button class="close-modal px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal - Enhanced for consistency -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4 text-red-500 dark:text-red-400">
                    <i class="fas fa-exclamation-triangle text-5xl"></i>
                </div>
                <h3 class="text-xl font-medium text-center mb-2 text-gray-900 dark:text-white">Delete Feedback</h3>
                <p class="text-center mb-6 text-gray-600 dark:text-gray-300">Are you sure you want to delete the feedback from <span id="delete-student-name" class="font-medium"></span>? This action cannot be undone.</p>
                <form id="deleteForm" method="POST" action="feedback.php" class="flex justify-center space-x-4">
                    <input type="hidden" id="delete-feedback-id" name="feedback_id">
                    <input type="hidden" name="delete_feedback" value="1">
                    <button type="button" class="cancel-delete px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 font-medium">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Notification - Enhanced styling -->
    <?php if (isset($_SESSION['delete_success'])): ?>
    <div id="successNotification" class="notification fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 dark:bg-green-900/30 dark:border-green-500 dark:text-green-300 p-4 rounded shadow-md z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span><?php echo $_SESSION['delete_success']; ?></span>
            <button class="ml-6 text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['delete_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['delete_error'])): ?>
    <div id="errorNotification" class="notification fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 dark:bg-red-900/30 dark:border-red-500 dark:text-red-300 p-4 rounded shadow-md z-50">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span><?php echo $_SESSION['delete_error']; ?></span>
            <button class="ml-6 text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <?php unset($_SESSION['delete_error']); ?>
    <?php endif; ?>
    
    <!-- Footer - Improved visibility to match dashboard -->
    <footer class="mt-auto py-4 border-t border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> Admin Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Initialize all interactive elements when DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            document.getElementById('mobile-menu-button').addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            });
            
            // User dropdown toggle - if exists
            const userMenuButton = document.getElementById('user-menu-button');
            if (userMenuButton) {
                userMenuButton.addEventListener('click', function() {
                    document.getElementById('user-dropdown').classList.toggle('hidden');
                });
            }
            
            // Close dropdowns when clicking outside
            window.addEventListener('click', function(e) {
                const userMenu = document.getElementById('user-menu-button');
                const userDropdown = document.getElementById('user-dropdown');
                
                if (userMenu && userDropdown && !userMenu.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
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
            
            // Search functionality
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const searchValue = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#feedbackTable tbody tr.feedback-item');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchValue)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show/hide no results message
                const noResults = document.querySelector('#feedbackTable tbody tr:not(.feedback-item)');
                if (rows.length > 0 && visibleCount === 0) {
                    if (!noResults) {
                        const tbody = document.querySelector('#feedbackTable tbody');
                        tbody.innerHTML += `
                            <tr id="noResultsRow">
                                <td colspan="7" class="px-6 py-10 text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-search text-3xl text-gray-400 mb-3"></i>
                                        <p class="text-gray-500 dark:text-gray-400">No matching feedback found</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                } else {
                    const noResultsRow = document.getElementById('noResultsRow');
                    if (noResultsRow) noResultsRow.remove();
                }
            });
            
            // FIXED: View feedback details button
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    try {
                        const feedbackData = JSON.parse(this.getAttribute('data-feedback'));
                        
                        // Populate modal with data
                        document.getElementById('modal-student-name').textContent = feedbackData.student_name;
                        document.getElementById('modal-student-id').textContent = feedbackData.id_number;
                        document.getElementById('modal-lab-number').textContent = 'Lab ' + feedbackData.lab_number;
                        document.getElementById('modal-date').textContent = feedbackData.date;
                        document.getElementById('modal-login-time').textContent = feedbackData.login_time;
                        document.getElementById('modal-logout-time').textContent = feedbackData.logout_time;
                        document.getElementById('modal-purpose').textContent = feedbackData.purpose || 'Not specified';
                        document.getElementById('modal-rating').textContent = feedbackData.rating + '/5';
                        
                        const starsContainer = document.getElementById('modal-stars');
                        starsContainer.innerHTML = '';
                        for (let i = 1; i <= 5; i++) {
                            const star = document.createElement('i');
                            star.className = i <= feedbackData.rating ? 'fas fa-star text-yellow-400' : 'far fa-star text-yellow-400';
                            starsContainer.appendChild(star);
                        }
                        
                        document.getElementById('modal-message').textContent = feedbackData.feedback_message || 'No comments provided';
                        
                        // Handle technical issues section
                        if (feedbackData.had_technical_issues) {
                            document.getElementById('no-issues-text').classList.add('hidden');
                            document.getElementById('issues-text').classList.remove('hidden');
                            document.getElementById('modal-issues-description').textContent = feedbackData.issues_description || 'No additional details provided';
                        } else {
                            document.getElementById('no-issues-text').classList.remove('hidden');
                            document.getElementById('issues-text').classList.add('hidden');
                        }
                        
                        // Show modal
                        document.getElementById('detailsModal').classList.remove('hidden');
                    } catch (error) {
                        console.error("Error processing feedback data:", error);
                    }
                });
            });
            
            // Close modal events
            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('detailsModal').classList.add('hidden');
                });
            });
            
            // Close modal when clicking outside
            document.getElementById('detailsModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
            
            // FIXED: Delete feedback functionality
            document.querySelectorAll('.delete-feedback').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const studentName = this.getAttribute('data-name');
                    
                    // Set values in the delete modal
                    document.getElementById('delete-feedback-id').value = id;
                    document.getElementById('delete-student-name').textContent = studentName;
                    
                    // Show the delete modal
                    document.getElementById('deleteModal').classList.remove('hidden');
                });
            });
            
            // Cancel delete button
            document.querySelectorAll('.cancel-delete').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('deleteModal').classList.add('hidden');
                });
            });
            
            // Close delete modal when clicking outside
            document.getElementById('deleteModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
        
        // Auto-hide notifications after 3 seconds
        const successNotification = document.getElementById('successNotification');
        const errorNotification = document.getElementById('errorNotification');
        
        if (successNotification) {
            setTimeout(() => {
                successNotification.style.opacity = '0';
                setTimeout(() => {
                    successNotification.remove();
                }, 500);
            }, 3000);
        }
        
        if (errorNotification) {
            setTimeout(() => {
                errorNotification.style.opacity = '0';
                setTimeout(() => {
                    errorNotification.remove();
                }, 500);
            }, 3000);
        }
    </script>
</body>
</html>
