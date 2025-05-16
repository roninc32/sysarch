<?php
session_start();
include 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

// Create schedule table if it doesn't exist
$create_schedule_table = "CREATE TABLE IF NOT EXISTS lab_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_number VARCHAR(50) NOT NULL,
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    professor VARCHAR(255),
    subject VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($create_schedule_table) !== TRUE) {
    // Handle table creation error
    $_SESSION['message'] = "Error creating schedule table: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

// Handle schedule creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_schedule'])) {
    $lab_number = $conn->real_escape_string($_POST['lab_number']);
    $day_of_week = $conn->real_escape_string($_POST['day_of_week']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $professor = $conn->real_escape_string($_POST['professor']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $sql = "INSERT INTO lab_schedules (lab_number, day_of_week, start_time, end_time, professor, subject, is_available, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $lab_number, $day_of_week, $start_time, $end_time, $professor, $subject, $is_available, $notes);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Schedule added successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: schedule.php");
    exit();
}

// Handle schedule update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_schedule'])) {
    $id = (int)$_POST['id'];
    $lab_number = $conn->real_escape_string($_POST['lab_number']);
    $day_of_week = $conn->real_escape_string($_POST['day_of_week']);
    $start_time = $conn->real_escape_string($_POST['start_time']);
    $end_time = $conn->real_escape_string($_POST['end_time']);
    $professor = $conn->real_escape_string($_POST['professor']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $notes = $conn->real_escape_string($_POST['notes']);
    
    $sql = "UPDATE lab_schedules SET lab_number=?, day_of_week=?, start_time=?, end_time=?, 
            professor=?, subject=?, is_available=?, notes=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssi", $lab_number, $day_of_week, $start_time, $end_time, $professor, $subject, $is_available, $notes, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Schedule updated successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: schedule.php");
    exit();
}

// Handle schedule deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_schedule'])) {
    $id = (int)$_POST['id'];
    
    $sql = "DELETE FROM lab_schedules WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Schedule deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting schedule: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: schedule.php");
    exit();
}

// Fetch all lab schedules grouped by day
$days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$schedule_by_day = [];

foreach ($days_of_week as $day) {
    $sql = "SELECT * FROM lab_schedules WHERE day_of_week=? ORDER BY lab_number, start_time";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $day);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule_by_day[$day] = $result->fetch_all(MYSQLI_ASSOC);
}

// Get labs with schedules
$labs_sql = "SELECT DISTINCT lab_number FROM lab_schedules ORDER BY lab_number";
$labs_result = $conn->query($labs_sql);
$labs = [];
while ($row = $labs_result->fetch_assoc()) {
    $labs[] = $row['lab_number'];
}

// Add default labs if none exist
if (empty($labs)) {
    $labs = ['524', '526', '528', '530', 'MAC Laboratory'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Schedules - Admin Dashboard</title>
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
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-outline:hover {
            background-color: var(--bg-secondary);
        }
        
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
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
        
        .schedule-grid {
            display: grid;
            grid-template-columns: 100px repeat(6, 1fr);
            grid-gap: 1px;
            background-color: var(--border-color);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .schedule-grid .header {
            background-color: var(--accent-light);
            color: var(--accent-color);
            font-weight: 600;
            text-align: center;
            padding: 12px;
        }
        
        .dark .schedule-grid .header {
            color: #f1f5f9;
        }
        
        .schedule-grid .time-slot {
            background-color: var(--card-bg);
            padding: 12px;
            text-align: center;
            font-weight: 500;
        }
        
        .schedule-grid .schedule-cell {
            background-color: var(--card-bg);
            padding: 12px;
            min-height: 80px;
        }
        
        .schedule-card {
            background-color: var(--bg-secondary);
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 8px;
            position: relative;
        }
        
        .schedule-card.available {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
        }
        
        .dark .schedule-card.available {
            background-color: rgba(16, 185, 129, 0.2);
        }
        
        .schedule-card.unavailable {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 3px solid #ef4444;
        }
        
        .dark .schedule-card.unavailable {
            background-color: rgba(239, 68, 68, 0.2);
        }
        
        .schedule-title {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .schedule-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .schedule-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .schedule-card:hover .schedule-actions {
            opacity: 1;
        }
        
        .schedule-tab-buttons {
            display: flex;
            margin-bottom: 16px;
            background-color: var(--card-bg);
            border-radius: 8px;
            padding: 4px;
            box-shadow: var(--card-shadow);
        }
        
        .schedule-tab-button {
            padding: 10px 20px;
            font-weight: 500;
            text-align: center;
            flex: 1;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .schedule-tab-button.active {
            background-color: var(--accent-color);
            color: white;
        }
        
        .schedule-tab-content {
            display: none;
        }
        
        .schedule-tab-content.active {
            display: block;
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
            
            .schedule-grid {
                display: block;
                overflow-x: auto;
            }
            
            .schedule-table {
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
                <a href="schedule.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>Schedules</span>
                </a>
                <a href="feedback.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-comments"></i></div>
                    <span>Feedback</span>
                </a>
                <a href="resources.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'resources.php' ? 'active' : ''; ?>">
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
    
    <!-- Main content -->
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
            <!-- Status Message -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['message_type'] == 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'; ?>">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title flex items-center">
                        <i class="fas fa-calendar-week mr-2 text-blue-500"></i>
                        Laboratory Schedules
                    </h2>
                    <button class="btn btn-primary" id="addScheduleBtn">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- View Selector Tabs -->
                    <div class="schedule-tab-buttons mb-6">
                        <div class="schedule-tab-button active" data-tab="weekly">
                            <i class="fas fa-calendar-week mr-2"></i>
                            Weekly View
                        </div>
                        <div class="schedule-tab-button" data-tab="lab">
                            <i class="fas fa-laptop mr-2"></i>
                            By Laboratory
                        </div>
                    </div>
                    
                    <!-- Weekly Schedule View -->
                    <div class="schedule-tab-content active" id="weekly-view">
                        <div class="overflow-x-auto">
                            <div class="schedule-table">
                                <div class="schedule-grid">
                                    <div class="header">Time</div>
                                    <div class="header">Monday</div>
                                    <div class="header">Tuesday</div>
                                    <div class="header">Wednesday</div>
                                    <div class="header">Thursday</div>
                                    <div class="header">Friday</div>
                                    <div class="header">Saturday</div>
                                    
                                    <?php
                                    // Time slots from 7am to 9:30pm - UPDATED
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
                                            echo '<div class="schedule-cell" id="cell-' . $day . '-' . $time_value . '">';
                                            
                                            // Display schedules for this time slot and day
                                            foreach ($schedule_by_day[$day] as $schedule) {
                                                $schedule_start = $schedule['start_time'];
                                                $schedule_end = $schedule['end_time'];
                                                
                                                // Check if this schedule falls within this time slot
                                                if ($time_value >= $schedule_start && $time_value < $schedule_end) {
                                                    $status_class = $schedule['is_available'] ? 'available' : 'unavailable';
                                                    $schedule_id = $schedule['id'];
                                                    $lab_number = $schedule['lab_number'];
                                                    $professor = $schedule['professor'];
                                                    $subject = $schedule['subject'];
                                                    $notes = $schedule['notes'];
                                                    
                                                    echo '<div class="schedule-card ' . $status_class . '" data-id="' . $schedule_id . '">';
                                                    echo '<div class="schedule-title">' . htmlspecialchars($subject) . ' (Lab ' . htmlspecialchars($lab_number) . ')</div>';
                                                    echo '<div class="schedule-meta">' . htmlspecialchars($professor) . '</div>';
                                                    echo '<div class="schedule-meta">' . date('g:i A', strtotime($schedule_start)) . ' - ' . date('g:i A', strtotime($schedule_end)) . '</div>';
                                                    
                                                    // Action buttons
                                                    echo '<div class="schedule-actions">';
                                                    echo '<button class="edit-schedule btn btn-sm btn-outline" data-id="' . $schedule_id . '"><i class="fas fa-edit"></i></button>';
                                                    echo '<button class="delete-schedule btn btn-sm btn-danger ml-1" data-id="' . $schedule_id . '"><i class="fas fa-trash"></i></button>';
                                                    echo '</div>';
                                                    
                                                    echo '</div>';
                                                }
                                            }
                                            
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Laboratory View -->
                    <div class="schedule-tab-content" id="lab-view">
                        <div class="flex flex-wrap gap-4 mb-6">
                            <?php foreach ($labs as $lab): ?>
                            <button class="lab-filter btn btn-outline <?php echo $lab === $labs[0] ? 'btn-primary text-white' : ''; ?>" data-lab="<?php echo $lab; ?>">
                                Lab <?php echo $lab; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php foreach ($labs as $lab): ?>
                        <div class="lab-schedule <?php echo $lab !== $labs[0] ? 'hidden' : ''; ?>" id="lab-<?php echo $lab; ?>">
                            <div class="card mb-6">
                                <div class="card-header">
                                    <h3 class="card-title">Lab <?php echo $lab; ?> Schedule</h3>
                                </div>
                                <div class="p-6">
                                    <table class="w-full border-collapse">
                                        <thead>
                                            <tr>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Day</th>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Time</th>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Subject</th>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Professor</th>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Status</th>
                                                <th class="border px-4 py-2 bg-gray-100 dark:bg-gray-700">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $found_schedules = false;
                                            foreach ($days_of_week as $day): 
                                                foreach ($schedule_by_day[$day] as $schedule):
                                                    if ($schedule['lab_number'] == $lab):
                                                        $found_schedules = true;
                                            ?>
                                            <tr>
                                                <td class="border px-4 py-2"><?php echo $day; ?></td>
                                                <td class="border px-4 py-2"><?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?></td>
                                                <td class="border px-4 py-2"><?php echo htmlspecialchars($schedule['subject']); ?></td>
                                                <td class="border px-4 py-2"><?php echo htmlspecialchars($schedule['professor']); ?></td>
                                                <td class="border px-4 py-2">
                                                    <?php if ($schedule['is_available']): ?>
                                                    <span class="px-2 py-1 rounded-full text-sm bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                        Available for Sit-In
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="px-2 py-1 rounded-full text-sm bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                        Not Available
                                                    </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="border px-4 py-2">
                                                    <button class="edit-schedule btn btn-sm btn-outline" data-id="<?php echo $schedule['id']; ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="delete-schedule btn btn-sm btn-danger ml-1" data-id="<?php echo $schedule['id']; ?>">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php 
                                                    endif;
                                                endforeach; 
                                            endforeach; 
                                            
                                            if (!$found_schedules):
                                            ?>
                                            <tr>
                                                <td colspan="6" class="border px-4 py-8 text-center">
                                                    <div class="flex flex-col items-center">
                                                        <i class="fas fa-calendar-times text-4xl opacity-30 mb-3 text-gray-400 dark:text-gray-500"></i>
                                                        <p class="text-lg font-medium">No schedules found for Lab <?php echo $lab; ?></p>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Click "Add Schedule" to create one.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Schedule Modal -->
    <div id="scheduleModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold" id="modalTitle">Add New Schedule</h3>
            </div>
            <div class="p-6">
                <form id="scheduleForm" action="schedule.php" method="POST">
                    <input type="hidden" name="id" id="schedule_id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="lab_number" class="block text-sm font-medium mb-2">Laboratory</label>
                            <select id="lab_number" name="lab_number" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab; ?>">Lab <?php echo $lab; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="day_of_week" class="block text-sm font-medium mb-2">Day</label>
                            <select id="day_of_week" name="day_of_week" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                <?php foreach ($days_of_week as $day): ?>
                                <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="start_time" class="block text-sm font-medium mb-2">Start Time</label>
                            <input type="time" id="start_time" name="start_time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="end_time" class="block text-sm font-medium mb-2">End Time</label>
                            <input type="time" id="end_time" name="end_time" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                        
                        <div>
                            <label for="professor" class="block text-sm font-medium mb-2">Professor</label>
                            <input type="text" id="professor" name="professor" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_available" name="is_available" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 h-5 w-5" checked>
                            <label for="is_available" class="ml-2 block text-sm font-medium">Available for Sit-In</label>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Check this if students can reserve sit-in sessions during this time</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="notes" class="block text-sm font-medium mb-2">Notes (Optional)</label>
                        <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 mt-6">
                        <button type="button" class="btn btn-outline" onclick="closeScheduleModal()">Cancel</button>
                        <button type="submit" id="submitButton" name="add_schedule" class="btn btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="p-6 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Delete Schedule</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete this schedule? This action cannot be undone.</p>
                
                <div class="flex justify-center space-x-3">
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                    <form action="schedule.php" method="POST">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="submit" name="delete_schedule" class="btn btn-danger">Delete</button>
                    </form>
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
        const tabButtons = document.querySelectorAll('.schedule-tab-button');
        const tabContents = document.querySelectorAll('.schedule-tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update active tab button
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show appropriate tab content
                tabContents.forEach(content => {
                    content.classList.remove('active');
                    if (content.id === tabId + '-view') {
                        content.classList.add('active');
                    }
                });
            });
        });
        
        // Lab filtering
        const labFilters = document.querySelectorAll('.lab-filter');
        const labSchedules = document.querySelectorAll('.lab-schedule');
        
        labFilters.forEach(filter => {
            filter.addEventListener('click', function() {
                const lab = this.getAttribute('data-lab');
                
                // Update active filter
                labFilters.forEach(btn => {
                    btn.classList.remove('btn-primary', 'text-white');
                });
                this.classList.add('btn-primary', 'text-white');
                
                // Show appropriate lab schedule
                labSchedules.forEach(schedule => {
                    schedule.classList.add('hidden');
                    if (schedule.id === 'lab-' + lab) {
                        schedule.classList.remove('hidden');
                    }
                });
            });
        });
        
        // Schedule modal handling
        const scheduleModal = document.getElementById('scheduleModal');
        const scheduleForm = document.getElementById('scheduleForm');
        const modalTitle = document.getElementById('modalTitle');
        const submitButton = document.getElementById('submitButton');
        
        // Open add schedule modal
        document.getElementById('addScheduleBtn').addEventListener('click', function() {
            modalTitle.textContent = 'Add New Schedule';
            submitButton.textContent = 'Add Schedule';
            submitButton.name = 'add_schedule';
            
            // Reset form
            scheduleForm.reset();
            document.getElementById('schedule_id').value = '';
            
            // Show modal
            scheduleModal.classList.remove('hidden');
        });
        
        // Handle edit schedule
        document.querySelectorAll('.edit-schedule').forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-id');
                
                // Fetch schedule data via AJAX
                fetch('get_schedule.php?id=' + scheduleId)
                    .then(response => response.json())
                    .then(schedule => {
                        // Populate form fields
                        document.getElementById('schedule_id').value = schedule.id;
                        document.getElementById('lab_number').value = schedule.lab_number;
                        document.getElementById('day_of_week').value = schedule.day_of_week;
                        document.getElementById('start_time').value = schedule.start_time;
                        document.getElementById('end_time').value = schedule.end_time;
                        document.getElementById('professor').value = schedule.professor;
                        document.getElementById('subject').value = schedule.subject;
                        document.getElementById('is_available').checked = schedule.is_available == 1;
                        document.getElementById('notes').value = schedule.notes;
                        
                        // Update modal title and button
                        modalTitle.textContent = 'Edit Schedule';
                        submitButton.textContent = 'Update Schedule';
                        submitButton.name = 'update_schedule';
                        
                        // Show modal
                        scheduleModal.classList.remove('hidden');
                    })
                    .catch(error => {
                        console.error('Error fetching schedule:', error);
                        alert('Error loading schedule data. Please try again.');
                    });
            });
        });
        
        function closeScheduleModal() {
            scheduleModal.classList.add('hidden');
        }
        
        // Handle schedule deletion
        document.querySelectorAll('.delete-schedule').forEach(button => {
            button.addEventListener('click', function() {
                const scheduleId = this.getAttribute('data-id');
                document.getElementById('delete_id').value = scheduleId;
                document.getElementById('deleteModal').classList.remove('hidden');
            });
        });
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        scheduleModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeScheduleModal();
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        
        // Validate time inputs
        document.getElementById('end_time').addEventListener('change', function() {
            const startTime = document.getElementById('start_time').value;
            const endTime = this.value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                this.value = '';
            }
        });
        
        document.getElementById('start_time').addEventListener('change', function() {
            const startTime = this.value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('Start time must be before end time');
                this.value = '';
            }
        });
    </script>
</body>
</html>
