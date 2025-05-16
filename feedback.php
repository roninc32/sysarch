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
            color: var(--text-secondary);
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

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px var(--shadow-color);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
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
            background-color: var (--accent-hover);
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
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
        
        /* Star rating display */
        .star-display {
            color: #ccc;
        }
        
        .star-filled {
            color: #ffb700;
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
        
        /* Notifications */
        .notification {
            transition: opacity 0.5s ease-out;
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
                <h1 class="page-title">Student Feedback</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search feedback" class="search-input" id="searchInput">
                </div>
                
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
            <!-- Feedback Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fadeIn">
                <!-- Total Feedback -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Total Feedback</h3>
                            <div class="w-10 h-10 rounded-lg bg-blue-500 flex items-center justify-center text-white">
                                <i class="fas fa-comments"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $stats['total_feedback']; ?></div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total submissions</p>
                    </div>
                </div>
                
                <!-- Average Rating -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Average Rating</h3>
                            <div class="w-10 h-10 rounded-lg bg-green-500 flex items-center justify-center text-white">
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                        <div class="flex mt-1">
                            <?php
                            $avg_rating = round($stats['avg_rating'] * 2) / 2; // Round to nearest 0.5
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star text-yellow-400 mr-1"></i>';
                                } else if ($i - 0.5 == $avg_rating) {
                                    echo '<i class="fas fa-star-half-alt text-yellow-400 mr-1"></i>';
                                } else {
                                    echo '<i class="far fa-star text-yellow-400 mr-1"></i>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <!-- Positive Feedback -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Positive Feedback</h3>
                            <div class="w-10 h-10 rounded-lg bg-indigo-500 flex items-center justify-center text-white">
                                <i class="fas fa-thumbs-up"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $stats['positive_feedback']; ?></div>
                        <?php $positive_percent = $stats['total_feedback'] > 0 ? round(($stats['positive_feedback'] / $stats['total_feedback']) * 100) : 0; ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $positive_percent; ?>% of total feedback</p>
                    </div>
                </div>
                
                <!-- Technical Issues -->
                <div class="card">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase">Technical Issues</h3>
                            <div class="w-10 h-10 rounded-lg bg-red-500 flex items-center justify-center text-white">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo $stats['issues_count']; ?></div>
                        <?php $issues_percent = $stats['total_feedback'] > 0 ? round(($stats['issues_count'] / $stats['total_feedback']) * 100) : 0; ?>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $issues_percent; ?>% reported issues</p>
                    </div>
                </div>
            </div>
            
            <!-- Feedback Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="fas fa-list mr-2 text-blue-500"></i>
                        <span>Student Feedback List</span>
                    </h2>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="feedbackTable">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Student
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Lab #
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Session Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Rating
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Technical Issues
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Submitted
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (empty($feedback_list)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-10 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="far fa-comment-dots text-5xl opacity-30 mb-4 text-gray-400 dark:text-gray-500"></i>
                                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No feedback submissions yet</p>
                                            <p class="text-base text-gray-600 dark:text-gray-400">Student feedback will appear here</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($feedback_list as $feedback): ?>
                                    <tr class="feedback-item hover:bg-gray-50 dark:hover:bg-gray-700">
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

    <!-- Details Modal -->
    <div id="detailsModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center border-b p-4 sticky top-0 bg-white dark:bg-gray-800 z-10">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Feedback Details</h3>
                <button class="close-modal text-gray-400 hover:text-gray-500" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6">
                <!-- Modal content will be populated by JavaScript -->
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
                
                <!-- Additional modal content -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Content will be populated by JavaScript -->
                </div>
                
                <div class="border-t pt-4 text-right">
                    <button class="close-modal px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6">
                <div class="flex items-center justify-center mb-4 text-red-500 dark:text-red-400">
                    <i class="fas fa-exclamation-triangle text-5xl"></i>
                </div>
                <h3 class="text-xl font-medium text-center mb-2 text-gray-900 dark:text-white">Delete Feedback</h3>
                <p class="text-center mb-6 text-gray-600 dark:text-gray-300">Are you sure you want to delete this feedback? This action cannot be undone.</p>
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
    
    <!-- Success/Error Notification -->
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
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
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
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // View feedback details button
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const feedbackData = JSON.parse(this.getAttribute('data-feedback'));
                
                // Populate modal with data
                document.getElementById('modal-student-name').textContent = feedbackData.student_name;
                document.getElementById('modal-student-id').textContent = feedbackData.id_number;
                
                // Show modal
                document.getElementById('detailsModal').classList.remove('hidden');
            });
        });
        
        // Close modal buttons
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('detailsModal').classList.add('hidden');
            });
        });
        
        // Delete feedback functionality
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
        
        // Auto-hide notifications after 3 seconds
        const successNotification = document.getElementById('successNotification');
        const errorNotification = document.getElementById('errorNotification');
        
        if (successNotification) {
            setTimeout(() => {
                successNotification.style.opacity = '0';
                setTimeout(() => successNotification.remove(), 500);
            }, 3000);
        }
        
        if (errorNotification) {
            setTimeout(() => {
                errorNotification.style.opacity = '0';
                setTimeout(() => errorNotification.remove(), 500);
            }, 3000);
        }
    </script>
</body>
</html>
