<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}
        
// Fetch students with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$sql_students = "SELECT * FROM users LIMIT ?, ?";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result_students = $stmt->get_result();
$students = $result_students->fetch_all(MYSQLI_ASSOC);

// Fetch announcements
$sql_announcements = "SELECT * FROM announcements ORDER BY created_at DESC";
$result_announcements = $conn->query($sql_announcements);
$announcements = $result_announcements->fetch_all(MYSQLI_ASSOC);

// Fetch sit-in records with pagination (only today's active sessions)
$sql_sit_in = "SELECT * FROM active_sit_ins WHERE DATE(date) = CURDATE() LIMIT ?, ?";
$stmt = $conn->prepare($sql_sit_in);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result_sit_in = $stmt->get_result();
$sit_in_records = $result_sit_in->fetch_all(MYSQLI_ASSOC);

// Get total count of today's sit-in sessions
$sql_active_count = "SELECT COUNT(*) as active_count FROM active_sit_ins WHERE DATE(date) = CURDATE()";
$result_active_count = $conn->query($sql_active_count);
$active_count = $result_active_count->fetch_assoc()['active_count'];

// Fetch language statistics from all sit-in records
$sql_languages = "SELECT sit_in_purpose, COUNT(*) as count 
                 FROM reservations 
                 GROUP BY sit_in_purpose 
                 ORDER BY count DESC";
$result_languages = $conn->query($sql_languages);
$language_stats = $result_languages->fetch_all(MYSQLI_ASSOC);

// Convert to JSON for JavaScript
$language_data = json_encode($language_stats);


$sql_lab_numbers = "SELECT lab_number, COUNT(*) as count 
                    FROM reservations 
                    GROUP BY lab_number 
                    ORDER BY count DESC";
$result_lab_numbers = $conn->query($sql_lab_numbers);
$lab_stats = $result_lab_numbers->fetch_all(MYSQLI_ASSOC);

$lab_data = json_encode($lab_stats);

// Handle announcement creation with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_announcement'])) {
    $content = trim($_POST['content']);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    $sql_create = "INSERT INTO announcements (content) VALUES (?)";
    $stmt = $conn->prepare($sql_create);
    $stmt->bind_param("s", $content);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle announcement edit with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = (int)$_POST['id'];
    $content = trim($_POST['content']);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    $sql_edit = "UPDATE announcements SET content=? WHERE id=?";
    $stmt = $conn->prepare($sql_edit);
    $stmt->bind_param("si", $content, $id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle announcement deletion with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $id = (int)$_POST['id'];
    $sql_delete = "DELETE FROM announcements WHERE id=?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle sit-in activity with validation & transaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $id_number = trim($_POST['id_number']);
    $purpose = trim($_POST['purpose']);
    $lab = trim($_POST['lab']);
    $sessions_left = (int)$_POST['sessions_left'] - 1;
    
    if ($sessions_left < 0) {
        header("Location: admin_dashboard.php?error=sessions");
        exit();
    }
    
    $conn->begin_transaction();
    try {
        $sql_update_sessions = "UPDATE users SET sessions_left=? WHERE id_number=?";
        $stmt = $conn->prepare($sql_update_sessions);
        $stmt->bind_param("is", $sessions_left, $id_number);
        $stmt->execute();

        $sql_insert_reservation = "INSERT INTO reservations (id_number, sit_in_purpose, lab_number, login_time, date) VALUES (?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql_insert_reservation);
        $stmt->bind_param("sss", $id_number, $purpose, $lab);
        $stmt->execute();

        $conn->commit();
        header("Location: admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_dashboard.php?error=transaction");
        exit();
    }
}

// Fetch recent reservations for notifications - Using sitin_reservation table
$sql_recent_reservations = "SELECT sr.id, sr.student_name, sr.purpose, sr.laboratory, 
                           sr.pc_number, sr.time_in, sr.date, sr.status
                           FROM sitin_reservation sr 
                           WHERE sr.status = 'pending'
                           ORDER BY sr.id DESC 
                           LIMIT 10";
$result_reservations = $conn->query($sql_recent_reservations);
$recent_reservations = [];
$notification_count = 0;

if ($result_reservations && $result_reservations->num_rows > 0) {
    while ($row = $result_reservations->fetch_assoc()) {
        $recent_reservations[] = $row;
    }
    $notification_count = count($recent_reservations);
}

// Secure Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
            color: var (--text-primary);
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
            color: var (--text-secondary);
            padding-top: 12px;
            border-top: 1px solid var(--border-color);
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            display: flex;
            gap: 16px;
            padding: 12px;
            border-radius: 8px;
            background-color: var(--bg-secondary);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 500;
            font-size: 14px;
            color: var (--text-primary);
            margin-bottom: 4px;
        }
        
        .activity-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .activity-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .activity-tag.blue {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
        }
        
        .activity-tag.purple {
            background-color: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }
        
        .activity-time {
            border-radius: 16px;
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .form-container {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        
        .button-group {
            display: flex;
            gap: 8px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var (--border-color);
            color: var(--text-primary);
        }
        
        .btn-outline:hover {
            background-color: var(--bg-secondary);
        }
        
        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
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
        
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: var(--bg-secondary);
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text-primary);
        }
        
        .notification-time {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .notification-content {
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .notification-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
        }
        
        .notification-meta {
            display: flex;
            gap: 12px;
        }
        
        .notification-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .notification-actions {
            display: flex;
            gap: 8px;
        }
        
        .notification-bell {
            position: relative;
            padding: 8px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .notification-bell:hover {
            color: var(--accent-color);
        }
        
        .notification-bell.has-notifications {
            color: var(--accent-color);
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
                <h1 class="page-title">Dashboard</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search" class="search-input">
                </div>
                
                <div class="notification-bell <?php echo $notification_count > 0 ? 'has-notifications' : ''; ?>">
                    <i class="fas fa-bell text-xl"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
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
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">TOTAL STUDENTS</div>
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($students); ?></div>
                    <div class="stat-description">Registered students in the system</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">ACTIVE SESSIONS</div>
                        <div class="stat-icon green">
                            <i class="fas fa-desktop"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo $active_count; ?></div>
                    <div class="stat-description">Current sit-in sessions today</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-title">ANNOUNCEMENTS</div>
                        <div class="stat-icon yellow">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                    </div>
                    <div class="stat-value"><?php echo count($announcements); ?></div>
                    <div class="stat-description">Published announcements</div>
                </div>
            </div>
            
            <!-- Content Panels -->
            <div class="panels-grid">
                <!-- Add the New Reservation Notifications Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-bell text-yellow-500"></i>
                            <span>Reservation Requests</span>
                            <?php if ($notification_count > 0): ?>
                                <span class="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-1"><?php echo $notification_count; ?> new</span>
                            <?php endif; ?>
                        </h2>
                        <a href="admin_reservation.php" class="btn btn-outline text-sm">View All</a>
                    </div>
                    
                    <div class="panel-content p-0">
                        <?php if (empty($recent_reservations)): ?>
                            <div class="text-center py-10">
                                <i class="fas fa-calendar-check text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No pending reservations</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">New requests will appear here</p>
                            </div>
                        <?php else: ?>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($recent_reservations as $reservation): ?>
                                    <div class="notification-item">
                                        <div class="notification-header">
                                            <div class="notification-title">
                                                <?php echo htmlspecialchars($reservation['student_name']); ?>
                                            </div>
                                            <div class="notification-time">
                                                <?php 
                                                    $reservationDate = strtotime($reservation['date']);
                                                    echo date('M d', $reservationDate); 
                                                    echo ' at ' . date('g:i A', strtotime($reservation['time_in']));
                                                ?>
                                            </div>
                                        </div>
                                        <div class="notification-content">
                                            Requested a sit-in session for <?php echo htmlspecialchars($reservation['purpose']); ?>
                                        </div>
                                        <div class="notification-footer">
                                            <div class="notification-meta">
                                                <div class="notification-tag">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span>Lab <?php echo htmlspecialchars($reservation['laboratory']); ?></span>
                                                </div>
                                                <div class="notification-tag bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200">
                                                    <i class="fas fa-clock"></i>
                                                    <span>Pending</span>
                                                </div>
                                            </div>
                                            <div class="notification-actions">
                                                <a href="admin_reservation.php?action=approve&id=<?php echo $reservation['id']; ?>" class="btn btn-sm bg-green-500 text-white hover:bg-green-600">Approve</a>
                                                <a href="admin_reservation.php?action=reject&id=<?php echo $reservation['id']; ?>" class="btn btn-sm bg-red-500 text-white hover:bg-red-600">Reject</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="p-4 text-center">
                                <a href="admin_reservation.php" class="text-blue-500 hover:underline text-sm font-medium">
                                    View all reservation requests
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Announcements Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </h2>
                        <button class="btn btn-primary" onclick="showAnnouncementForm()">
                            <i class="fas fa-plus"></i>
                            <span>New</span>
                        </button>
                    </div>
                    
                    <div class="panel-content">
                        <!-- Announcement Form -->
                        <div class="form-container hidden" id="announcementForm">
                            <form method="post" action="admin_dashboard.php">
                                <div class="form-group">
                                    <label class="form-label" for="title">Title</label>
                                    <input type="text" id="title" name="title" class="form-control" placeholder="Enter title" required>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="content">Content</label>
                                    <textarea id="content" name="content" class="form-control" rows="4" placeholder="Enter announcement content" required></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <button type="button" class="btn btn-outline" onclick="showAnnouncementForm()">
                                        Cancel
                                    </button>
                                    <button type="submit" name="create_announcement" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                        <span>Post</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Announcements List -->
                        <div class="announcement-list">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-10">
                                    <i class="far fa-bell-slash text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No announcements yet</p>
                                    <p class="text-base text-gray-600 dark:text-gray-400">Create your first announcement</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item">
                                        <h3 class="announcement-title"><?php echo isset($announcement['title']) ? htmlspecialchars($announcement['title']) : 'Announcement'; ?></h3>
                                        <div class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></div>
                                        <div class="announcement-footer">
                                            <span><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></span>
                                            <div class="button-group">
                                                <button class="btn btn-sm btn-outline" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(addslashes($announcement['content'])); ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <form method="post" action="admin_dashboard.php" class="inline">
                                                    <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                                    <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities Panel -->
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-clipboard-check"></i>
                            <span>Recent Activities</span>
                        </h2>
                    </div>
                    
                    <div class="panel-content">
                        <div class="activity-list">
                            <?php if (empty($sit_in_records)): ?>
                                <div class="text-center py-10">
                                    <i class="fas fa-clipboard text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                    <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No active sessions today</p>
                                    <p class="text-base text-gray-600 dark:text-gray-400">Active sessions will appear here</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sit_in_records as $record): ?>
                                    <div class="activity-item">
                                        <div class="activity-content">
                                            <h3 class="activity-title"><?php echo htmlspecialchars($record['name']); ?></h3>
                                            <div class="activity-meta">
                                                <div class="activity-tag blue">
                                                    <i class="fas fa-laptop"></i>
                                                    <span>Lab <?php echo htmlspecialchars($record['lab_number']); ?></span>
                                                </div>
                                                <div class="activity-tag purple">
                                                    <i class="fas fa-code"></i>
                                                    <span><?php echo htmlspecialchars($record['sit_in_purpose']); ?></span>
                                                </div>
                                                <div class="activity-time">
                                                    <?php echo date('g:i A', strtotime($record['login_time'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="panels-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-chart-pie"></i>
                            <span>Language Distribution</span>
                        </h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="languagePieChart"></canvas>
                    </div>
                </div>
                
                <div class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-chart-bar"></i>
                            <span>Laboratory Usage</span>
                        </h2>
                    </div>
                    <div class="chart-container">
                        <canvas id="laboratoryBarChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Chart.js before the existing script tag -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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
        
        // Announcement form toggle
        function showAnnouncementForm() {
            const form = document.getElementById('announcementForm');
            form.classList.toggle('hidden');
        }
        
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
            
            updateChartsTheme();
        });
        
        // Edit announcement functionality
        function editAnnouncement(id, content) {
            // Create modal backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            backdrop.id = 'editModal';
            
            // Create modal content
            const modal = document.createElement('div');
            modal.className = 'bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl border border-gray-200 dark:border-gray-700';
            modal.innerHTML = `
                <h3 class="text-xl font-bold mb-4 border-b pb-3 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Edit Announcement</h3>
                <form id="editForm" method="POST" action="admin_dashboard.php">
                    <input type="hidden" name="id" value="${id}">
                    <div class="mb-6">
                        <label for="editContent" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">Content</label>
                        <textarea id="editContent" name="content" rows="8" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg 
                            bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500">${content}</textarea>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" class="px-5 py-2 border border-gray-300 dark:border-gray-600 
                            rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-800 dark:text-white" onclick="closeEditModal()">
                            Cancel
                        </button>
                        <button type="submit" name="edit_announcement" class="bg-blue-500 hover:bg-blue-600 
                            text-white px-5 py-2 rounded-md transition-colors font-medium">
                            Save Changes
                        </button>
                    </div>
                </form>
            `;
            
            backdrop.appendChild(modal);
            document.body.appendChild(backdrop);
            
            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) {
                    closeEditModal();
                }
            });
            
            setTimeout(() => {
                document.getElementById('editContent').focus();
            }, 100);
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                document.body.removeChild(modal);
            }
        }

        function updateChartsTheme() {
            // Get current theme
            const isDarkMode = document.documentElement.classList.contains('dark');
            
            // Update chart theme colors
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e5e7eb' : '#111827';
            const fontSize = 14;
            
            // Update and re-render charts
            if (window.laboratoryChart) {
                window.laboratoryChart.options.scales.x.grid.color = gridColor;
                window.laboratoryChart.options.scales.y.grid.color = gridColor;
                window.laboratoryChart.options.scales.x.ticks.color = textColor;
                window.laboratoryChart.options.scales.y.ticks.color = textColor;
                window.laboratoryChart.options.scales.x.ticks.font = { size: fontSize, weight: 'bold' };
                window.laboratoryChart.options.scales.y.ticks.font = { size: fontSize, weight: 'bold' };
                window.laboratoryChart.update();
            }
            
            if (window.languageChart) {
                window.languageChart.options.plugins.legend.labels.color = textColor;
                window.languageChart.options.plugins.legend.labels.font = { size: fontSize, weight: 'bold' };
                window.languageChart.update();
            }
        }
        
        // Chart initialization
        document.addEventListener('DOMContentLoaded', function() {
            const laboratoryStats = <?php echo $lab_data; ?>;
            const languageStats = <?php echo $language_data; ?>;
            
            // Get current theme
            const isDarkMode = document.documentElement.classList.contains('dark');
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e5e7eb' : '#111827';
            
            // Lab data
            const labLabels = laboratoryStats.map(item => `Lab ${item.lab_number}`);
            const labData = laboratoryStats.map(item => item.count);
            
            // Language data
            const languageLabels = languageStats.map(item => item.sit_in_purpose);
            const languageData = languageStats.map(item => item.count);
            
            // Colors with higher saturation for better visibility
            const colors = [
                '#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                '#EC4899', '#06B6D4', '#6366F1', '#D946EF', '#F97316'
            ];
            
            // Extend colors array if needed
            const getColors = (count) => {
                if (count <= colors.length) return colors.slice(0, count);
                return Array(count).fill().map(() => `#${Math.floor(Math.random()*16777215).toString(16).padStart(6, '0')}`);
            };
            
            // Bar Chart with theme support and better visibility
            window.laboratoryChart = new Chart(document.getElementById('laboratoryBarChart'), {
                type: 'bar',
                data: {
                    labels: labLabels,
                    datasets: [{
                        label: 'Number of Sessions',
                        data: labData,
                        backgroundColor: getColors(labLabels.length),
                        borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.3)' : 'rgba(0, 0, 0, 0.3)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1F2937' : 'white',
                            titleColor: isDarkMode ? '#F9FAFB' : '#111827',
                            bodyColor: isDarkMode ? '#F3F4F6' : '#374151',
                            borderColor: isDarkMode ? '#374151' : '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            titleFont: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor,
                                lineWidth: 1
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                lineWidth: 1
                            },
                            ticks: {
                                color: textColor,
                                precision: 0,
                                stepSize: 1,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });

            // Pie Chart with theme support and better visibility
            window.languageChart = new Chart(document.getElementById('languagePieChart'), {
                type: 'pie',
                data: {
                    labels: languageLabels,
                    datasets: [{
                        data: languageData,
                        backgroundColor: getColors(languageLabels.length),
                        borderColor: isDarkMode ? '#1F2937' : '#FFFFFF',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                padding: 20,
                                usePointStyle: true,
                                boxWidth: 10
                            }
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1F2937' : 'white',
                            titleColor: isDarkMode ? '#F9FAFB' : '#111827',
                            bodyColor: isDarkMode ? '#F3F4F6' : '#374151',
                            borderColor: isDarkMode ? '#374151' : '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            titleFont: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    }
                }
            });
        });
        
        // Notification bell click to scroll to notifications panel
        document.querySelector('.notification-bell').addEventListener('click', function() {
            const notificationPanel = document.querySelector('.panel-title i.fas.fa-bell').closest('.panel');
            notificationPanel.scrollIntoView({ behavior: 'smooth' });
            
            // Highlight the panel briefly
            notificationPanel.classList.add('ring-2', 'ring-accent-color', 'ring-opacity-50');
            setTimeout(() => {
                notificationPanel.classList.remove('ring-2', 'ring-accent-color', 'ring-opacity-50');
            }, 2000);
        });
    </script>
</body>
</html>