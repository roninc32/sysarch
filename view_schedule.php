<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];

// Get student information
$sql = "SELECT * FROM users WHERE id_number='$id_number'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first_name = $row["first_name"];
    $last_name = $row["last_name"];
    $course_level = $row["course_level"];
    $course = $row["course"];
} else {
    echo "No user found.";
    exit();
}

// Initialize filters
$day_filter = isset($_GET['day']) ? $_GET['day'] : '';
$lab_filter = isset($_GET['lab']) ? $_GET['lab'] : '';
$availability_filter = isset($_GET['availability']) ? (int)$_GET['availability'] : 1; // Default to available schedules

// Fetch days of week for filter
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Fetch lab numbers for filter
$labs_query = "SELECT DISTINCT lab_number FROM lab_schedules ORDER BY lab_number";
$result_labs = $conn->query($labs_query);
$labs = [];

if ($result_labs && $result_labs->num_rows > 0) {
    while ($row = $result_labs->fetch_assoc()) {
        $labs[] = $row['lab_number'];
    }
} else {
    // Default lab numbers if none found
    $labs = ['524', '526', '528', '530', 'MAC Laboratory'];
}

// Build query with filters
$sql_schedules = "SELECT * FROM lab_schedules WHERE 1=1";

if (!empty($day_filter)) {
    $sql_schedules .= " AND day_of_week = '" . $conn->real_escape_string($day_filter) . "'";
}

if (!empty($lab_filter)) {
    $sql_schedules .= " AND lab_number = '" . $conn->real_escape_string($lab_filter) . "'";
}

if ($availability_filter !== null) {
    $sql_schedules .= " AND is_available = " . $availability_filter;
}

$sql_schedules .= " ORDER BY day_of_week, start_time";

// Execute query
$result_schedules = $conn->query($sql_schedules);
$schedules = [];

// Group schedules by day
$schedules_by_day = [];
foreach ($days_of_week as $day) {
    $schedules_by_day[$day] = [];
}

// Fetch schedules
if ($result_schedules && $result_schedules->num_rows > 0) {
    while ($row = $result_schedules->fetch_assoc()) {
        $schedules[] = $row;
        $schedules_by_day[$row['day_of_week']][] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedules</title>
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
        
        .schedule-item {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s;
            border-left: 3px solid var(--accent-color);
        }
        
        .schedule-item:hover {
            transform: translateX(5px);
        }
        
        .schedule-item.unavailable {
            border-left-color: #ef4444;
        }
        
        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .schedule-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
        }
        
        .schedule-professor {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        
        .schedule-details {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        
        .schedule-detail {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .schedule-detail i {
            width: 16px;
            text-align: center;
        }
        
        .schedule-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-available {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .badge-unavailable {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-primary);
            font-size: 14px;
            min-width: 140px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .tab-buttons {
            display: flex;
            margin-bottom: 16px;
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 4px;
            box-shadow: var(--card-shadow);
        }
        
        .tab-button {
            padding: 10px 20px;
            font-weight: 500;
            text-align: center;
            flex: 1;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .tab-button.active {
            background-color: var(--accent-color);
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .week-view {
            display: grid;
            grid-template-columns: 100px repeat(6, 1fr);
            grid-gap: 1px;
            background-color: var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .week-view .header {
            background-color: var(--accent-light);
            color: var(--text-primary);
            font-weight: 600;
            text-align: center;
            padding: 12px;
        }
        
        .week-view .time-slot {
            background-color: var(--card-bg);
            padding: 12px;
            text-align: center;
            font-weight: 500;
        }
        
        .week-view .schedule-cell {
            background-color: var(--card-bg);
            padding: 8px;
            min-height: 80px;
        }
        
        .cell-content {
            font-size: 12px;
            padding: 5px;
            border-radius: 4px;
            margin-bottom: 4px;
        }
        
        .cell-content.available {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 2px solid #10b981;
        }
        
        .cell-content.unavailable {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 2px solid #ef4444;
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 64px;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .empty-description {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 400px;
            line-height: 1.5;
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
            
            .topbar {
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block !important;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .week-view {
                display: block;
                overflow-x: auto;
            }
            
            .week-content {
                min-width: 800px;
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
                <a href="dashboard.php" class="nav-item">
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
                <a href="view_schedule.php" class="nav-item active">
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
                <h1 class="page-title">Laboratory Schedules</h1>
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
            <!-- Filter Section -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title flex items-center">
                        <i class="fas fa-filter mr-2 text-blue-500"></i>
                        Filter Options
                    </h2>
                </div>
                
                <div class="p-4">
                    <form action="view_schedule.php" method="GET" class="filter-bar">
                        <div class="filter-group">
                            <label class="filter-label" for="day">Day</label>
                            <select name="day" id="day" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Days</option>
                                <?php foreach ($days_of_week as $day): ?>
                                    <option value="<?php echo $day; ?>" <?php echo $day_filter === $day ? 'selected' : ''; ?>>
                                        <?php echo $day; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label" for="lab">Laboratory</label>
                            <select name="lab" id="lab" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Labs</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?php echo $lab; ?>" <?php echo $lab_filter === $lab ? 'selected' : ''; ?>>
                                        Lab <?php echo $lab; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label" for="availability">Availability</label>
                            <select name="availability" id="availability" class="filter-select" onchange="this.form.submit()">
                                <option value="1" <?php echo $availability_filter === 1 ? 'selected' : ''; ?>>Available Only</option>
                                <option value="0" <?php echo $availability_filter === 0 ? 'selected' : ''; ?>>Unavailable Only</option>
                                <option value="" <?php echo $availability_filter === null ? 'selected' : ''; ?>>All</option>
                            </select>
                        </div>
                        
                        <?php if (!empty($day_filter) || !empty($lab_filter) || $availability_filter !== null): ?>
                            <div class="filter-group self-end">
                                <a href="view_schedule.php" class="btn btn-outline">
                                    <i class="fas fa-times mr-1"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Schedule View Tabs -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title flex items-center">
                        <i class="fas fa-calendar-week mr-2 text-blue-500"></i>
                        Laboratory Schedules
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="tab-buttons mb-6">
                        <div class="tab-button active" data-tab="list-view">
                            <i class="fas fa-list mr-2"></i>
                            List View
                        </div>
                        <div class="tab-button" data-tab="week-view">
                            <i class="fas fa-calendar-week mr-2"></i>
                            Weekly View
                        </div>
                    </div>
                    
                    <!-- List View Tab -->
                    <div class="tab-content active" id="list-view">
                        <?php if (empty($schedules)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times empty-icon"></i>
                                <h2 class="empty-title">No schedules found</h2>
                                <p class="empty-description">
                                    No laboratory schedules match your current filter criteria.
                                    Try adjusting your filters or check back later.
                                </p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($days_of_week as $day): ?>
                                <?php if (!empty($schedules_by_day[$day]) && (empty($day_filter) || $day_filter === $day)): ?>
                                    <h3 class="text-lg font-semibold mb-3 text-gray-700 dark:text-gray-300"><?php echo $day; ?></h3>
                                    
                                    <?php foreach ($schedules_by_day[$day] as $schedule): ?>
                                        <div class="schedule-item <?php echo $schedule['is_available'] ? '' : 'unavailable'; ?>">
                                            <div class="schedule-header">
                                                <div class="schedule-title"><?php echo htmlspecialchars($schedule['subject']); ?></div>
                                                <div class="schedule-badge <?php echo $schedule['is_available'] ? 'badge-available' : 'badge-unavailable'; ?>">
                                                    <?php echo $schedule['is_available'] ? 'Available' : 'Not Available'; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="schedule-professor">
                                                <i class="fas fa-user-tie mr-2"></i>
                                                <?php echo htmlspecialchars($schedule['professor']); ?>
                                            </div>
                                            
                                            <div class="schedule-details">
                                                <div class="schedule-detail">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?></span>
                                                </div>
                                                
                                                <div class="schedule-detail">
                                                    <i class="fas fa-laptop"></i>
                                                    <span>Lab <?php echo htmlspecialchars($schedule['lab_number']); ?></span>
                                                </div>
                                                
                                                <?php if (!empty($schedule['notes'])): ?>
                                                    <div class="schedule-detail">
                                                        <i class="fas fa-sticky-note"></i>
                                                        <span><?php echo htmlspecialchars($schedule['notes']); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($schedule['is_available']): ?>
                                                <div class="mt-3">
                                                    <a href="reservation.php?lab=<?php echo urlencode($schedule['lab_number']); ?>&day=<?php echo urlencode($schedule['day_of_week']); ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-calendar-plus mr-1"></i> Reserve This Session
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="mb-8"></div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Weekly View Tab -->
                    <div class="tab-content" id="week-view">
                        <div class="overflow-x-auto">
                            <div class="week-content">
                                <div class="week-view">
                                    <div class="header">Time</div>
                                    <div class="header">Monday</div>
                                    <div class="header">Tuesday</div>
                                    <div class="header">Wednesday</div>
                                    <div class="header">Thursday</div>
                                    <div class="header">Friday</div>
                                    <div class="header">Saturday</div>
                                    
                                    <?php
                                    // Time slots from 7am to 9:30pm
                                    $time_slots = [
                                        '07:00:00' => '7:00 AM',
                                        '08:00:00' => '8:00 AM',
                                        '09:00:00' => '9:00 AM',
                                        '10:00:00' => '10:00 AM',
                                        '11:00:00' => '11:00 AM',
                                        '12:00:00' => '12:00 PM',
                                        '13:00:00' => '1:00 PM',
                                        '14:00:00' => '2:00 PM',
                                        '15:00:00' => '3:00 PM',
                                        '16:00:00' => '4:00 PM',
                                        '17:00:00' => '5:00 PM',
                                        '18:00:00' => '6:00 PM',
                                        '19:00:00' => '7:00 PM',
                                        '20:00:00' => '8:00 PM',
                                        '21:00:00' => '9:00 PM',
                                        '21:30:00' => '9:30 PM',
                                    ];
                                    
                                    foreach ($time_slots as $time_value => $time_display) {
                                        echo '<div class="time-slot">' . $time_display . '</div>';
                                        
                                        foreach ($days_of_week as $day) {
                                            echo '<div class="schedule-cell">';
                                            
                                            // Show schedules for this day and time slot
                                            $found_schedules = false;
                                            foreach ($schedules_by_day[$day] as $schedule) {
                                                // Check if this schedule applies to the current time slot and matches filters
                                                if ($time_value >= $schedule['start_time'] && $time_value < $schedule['end_time']) {
                                                    if ((!empty($lab_filter) && $schedule['lab_number'] != $lab_filter) || 
                                                        ($availability_filter !== null && $schedule['is_available'] != $availability_filter)) {
                                                        continue;
                                                    }
                                                    
                                                    $found_schedules = true;
                                                    $status_class = $schedule['is_available'] ? 'available' : 'unavailable';
                                                    
                                                    echo '<div class="cell-content ' . $status_class . '">';
                                                    echo '<div class="font-semibold text-xs">' . htmlspecialchars($schedule['subject']) . '</div>';
                                                    echo '<div class="text-xs">Lab ' . htmlspecialchars($schedule['lab_number']) . '</div>';
                                                    echo '</div>';
                                                }
                                            }
                                            
                                            if (!$found_schedules) {
                                                echo '<div class="h-full w-full"></div>';
                                            }
                                            
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
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
        
        // Tab switching
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update active tab button
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show appropriate tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId) {
                        content.classList.add('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
