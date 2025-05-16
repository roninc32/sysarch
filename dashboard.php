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
    $profile_image = isset($row["profile_image"]) && !empty($row["profile_image"]) ? $row["profile_image"] : 'assets/images/profile.jpg';
    $last_name = $row["last_name"];
    $first_name = $row["first_name"];
    $middle_name = $row["middle_name"];
    $course_level = $row["course_level"];
    $email = $row["email"];
    $course = $row["course"];
    $address = $row["address"];
    $points = isset($row["points"]) ? $row["points"] : 0;
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

// Fetch leaderboard data (top 10 students by points)
$sql_leaderboard = "SELECT id_number, first_name, last_name, points 
                   FROM users 
                   ORDER BY points DESC 
                   LIMIT 10";
$result_leaderboard = $conn->query($sql_leaderboard);
$leaderboard = [];
if ($result_leaderboard->num_rows > 0) {
    while ($row = $result_leaderboard->fetch_assoc()) {
        $leaderboard[] = $row;
    }
}

// Fetch announcements
$sql_announcements = "SELECT * FROM announcements ORDER BY created_at DESC";
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if ($result_announcements->num_rows > 0) {
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// Fetch lab schedules
$sql_schedules = "SELECT * FROM lab_schedules WHERE is_available = 1 ORDER BY day_of_week, start_time LIMIT 5";
$result_schedules = $conn->query($sql_schedules);
$schedules = [];
if ($result_schedules && $result_schedules->num_rows > 0) {
    while ($row = $result_schedules->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Fetch resources
$sql_resources = "SELECT * FROM resources ORDER BY created_at DESC LIMIT 5";
$result_resources = $conn->query($sql_resources);
$resources = [];
if ($result_resources && $result_resources->num_rows > 0) {
    while ($row = $result_resources->fetch_assoc()) {
        $resources[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
        
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .card-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .stat-title {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .stat-icon.blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        
        .stat-icon.green {
            background: linear-gradient(135deg, #10b981, #059669);
        }
        
        .stat-icon.yellow {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .stat-description {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
        }
        
        .panels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }
        
        .panel {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .panel-header {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .panel-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .panel-title i {
            color: var(--accent-color);
        }
        
        .panel-content {
            padding: 20px;
            overflow-y: auto;
            max-height: 400px;
        }
        
        .announcement-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .announcement-item {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            border-left: 3px solid var(--accent-color);
        }
        
        .announcement-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .announcement-content {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .announcement-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-secondary);
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }
        
        .schedule-list, .resource-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .schedule-item, .resource-item {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            transition: all 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .schedule-item:hover, .resource-item:hover {
            transform: translateX(5px);
        }
        
        .schedule-icon, .resource-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 16px;
            flex-shrink: 0;
        }
        
        .schedule-icon {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
        }
        
        .resource-icon {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .schedule-details, .resource-details {
            flex: 1;
        }
        
        .schedule-title, .resource-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .schedule-meta, .resource-meta {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            background-color: var(--accent-light);
            color: var(--accent-color);
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
            
            .panels-grid {
                grid-template-columns: 1fr;
            }
            
            .topbar {
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="logo-text">SIT-IN Portal</div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <a href="dashboard.php" class="nav-item active">
                    <div class="nav-icon"><i class="fas fa-home"></i></div>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Account</div>
                <a href="edit_student_info.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-user"></i></div>
                    <span>Profile</span>
                </a>
                <a href="history.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-history"></i></div>
                    <span>Session History</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Actions</div>
                <a href="reservation.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar"></i></div>
                    <span>Make a Reservation</span>
                </a>
                <a href="view_schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>View Schedules</span>
                </a>
                <a href="view_resources.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-cube"></i></div>
                    <span>Browse Resources</span>
                </a>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar"><?php echo substr($first_name, 0, 1); ?></div>
                <div class="user-details">
                    <div class="user-name"><?php echo $first_name . ' ' . $last_name; ?></div>
                    <div class="user-role"><?php echo $course . ' - ' . $course_level; ?></div>
                </div>
                <div>
                    <a href="logout.php" title="Logout">
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
                <h1 class="page-title">Student Dashboard</h1>
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
            <!-- Welcome Message -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold">Welcome, <?php echo $first_name; ?>!</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Here's your dashboard overview</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">SESSIONS LEFT</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $sessions_left; ?></div>
                    <div class="stat-description">Available sit-in sessions</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">REWARD POINTS</div>
                        <div class="stat-icon yellow">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $points; ?></div>
                    <div class="stat-description">Earned from attendance</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">QUICK ACTIONS</div>
                        <div class="stat-icon green">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="reservation.php" class="btn btn-primary w-full mb-2">
                            <i class="fas fa-calendar-plus mr-2"></i> Make a Reservation
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Content Panels -->
            <div class="panels-grid">
                <!-- Announcements Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </h2>
                    </div>
                    
                    <div class="panel-content">
                        <!-- Announcements List -->
                        <div class="announcement-list">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-10">
                                    <i class="far fa-bell-slash text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No announcements yet</p>
                                    <p class="text-base text-gray-600 dark:text-gray-400">Check back later for updates</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item">
                                        <h3 class="announcement-title"><?php echo isset($announcement['title']) ? htmlspecialchars($announcement['title']) : 'Announcement'; ?></h3>
                                        <div class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></div>
                                        <div class="announcement-footer">
                                            <span><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Leaderboard Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-trophy"></i>
                            <span>Leaderboard</span>
                        </h2>
                    </div>
                    
                    <div class="panel-content">
                        <?php if (empty($leaderboard)): ?>
                            <div class="text-center py-10">
                                <i class="fas fa-chart-bar text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No leaderboard data</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">Check back later</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($leaderboard as $index => $student): ?>
                                    <div class="flex items-center p-3 rounded-lg <?php echo $student['id_number'] == $id_number ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800/50'; ?>">
                                        <div class="w-8 h-8 flex items-center justify-center rounded-full <?php 
                                            if ($index === 0) echo 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'; 
                                            elseif ($index === 1) echo 'bg-gray-100 text-gray-800 dark:bg-gray-700/50 dark:text-gray-300';
                                            elseif ($index === 2) echo 'bg-amber-100 text-amber-800 dark:bg-amber-900/20 dark:text-amber-300';
                                            else echo 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
                                        ?> font-semibold mr-3">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="flex-1">
                                            <span class="font-medium text-sm"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                                            <?php if ($student['id_number'] == $id_number): ?>
                                                <span class="ml-2 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded-full">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="font-semibold text-sm text-blue-600 dark:text-blue-400"><?php echo $student['points']; ?> pts</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Lab Schedules and Resources -->
            <div class="panels-grid">
                <!-- Lab Schedules Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-calendar-week"></i>
                            <span>Available Lab Schedules</span>
                        </h2>
                        <a href="view_schedule.php" class="btn btn-outline text-sm">View All</a>
                    </div>
                    
                    <div class="panel-content">
                        <div class="schedule-list">
                            <?php if (empty($schedules)): ?>
                                <div class="text-center py-10">
                                    <i class="fas fa-calendar-times text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No schedules available</p>
                                    <p class="text-base text-gray-600 dark:text-gray-400">Check back later for available lab sessions</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <div class="schedule-item">
                                        <div class="flex items-center">
                                            <div class="schedule-icon">
                                                <i class="fas fa-laptop"></i>
                                            </div>
                                            <div class="schedule-details">
                                                <div class="schedule-title"><?php echo htmlspecialchars($schedule['subject']); ?></div>
                                                <div class="schedule-meta">
                                                    <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($schedule['professor']); ?></span>
                                                    <span><i class="fas fa-clock mr-1"></i> <?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge">Lab <?php echo htmlspecialchars($schedule['lab_number']); ?></span>
                                            <span class="badge ml-2"><?php echo htmlspecialchars($schedule['day_of_week']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($schedules) >= 5): ?>
                                    <div class="text-center mt-4">
                                        <a href="view_schedule.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">View all available schedules →</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Resources Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-cube"></i>
                            <span>Learning Resources</span>
                        </h2>
                        <a href="view_resources.php" class="btn btn-outline text-sm">Browse All</a>
                    </div>
                    
                    <div class="panel-content">
                        <div class="resource-list">
                            <?php if (empty($resources)): ?>
                                <div class="text-center py-10">
                                    <i class="fas fa-books text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No resources available</p>
                                    <p class="text-base text-gray-600 dark:text-gray-400">Check back later for learning materials</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($resources as $resource): ?>
                                    <div class="resource-item">
                                        <div class="flex items-center">
                                            <div class="resource-icon">
                                                <i class="<?php echo !empty($resource['file_path']) ? 'fas fa-file-alt' : 'fas fa-cube'; ?>"></i>
                                            </div>
                                            <div class="resource-details">
                                                <div class="resource-title"><?php echo htmlspecialchars($resource['name']); ?></div>
                                                <div class="resource-meta">
                                                    <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($resource['professor']); ?></span>
                                                    <span><i class="fas fa-calendar-alt mr-1"></i> <?php echo date('M d, Y', strtotime($resource['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if (!empty($resource['file_path'])): ?>
                                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($resources) >= 5): ?>
                                    <div class="text-center mt-4">
                                        <a href="view_resources.php" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Browse all resources →</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
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
        
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or use system preference
        const darkModeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const savedTheme = localStorage.getItem('theme');
        
        if (savedTheme === 'dark' || (!savedTheme && darkModeMediaQuery.matches)) {
            html.classList.add('dark');
            darkModeToggle.checked = true;
        }
        
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        });
    </script>
</body>
</html>