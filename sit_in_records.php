<?php
include 'config.php';

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$history_page = isset($_GET['history_page']) ? (int)$_GET['history_page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$history_offset = ($history_page - 1) * $records_per_page;

// Get total counts for pagination
$total_current = $conn->query("SELECT COUNT(*) as count FROM active_sit_ins")->fetch_assoc()['count'];
$total_history = $conn->query("SELECT COUNT(*) as count FROM reservations WHERE logout_time IS NOT NULL")->fetch_assoc()['count'];

$total_current_pages = ceil($total_current / $records_per_page);
$total_history_pages = ceil($total_history / $records_per_page);

// Fetch current sit-in records with pagination
$sql_current = "SELECT * FROM active_sit_ins ORDER BY date DESC, login_time DESC LIMIT $records_per_page OFFSET $offset";
$result_current = $conn->query($sql_current);
$current_records = [];
if ($result_current->num_rows > 0) {
    while ($row = $result_current->fetch_assoc()) {
        $current_records[] = $row;
    }
}

// Fetch historical records with pagination
$sql_history = "SELECT * FROM reservations WHERE logout_time IS NOT NULL 
                ORDER BY date DESC, logout_time DESC, login_time DESC 
                LIMIT $records_per_page OFFSET $history_offset";
$result_history = $conn->query($sql_history);
$history_records = [];
if ($result_history->num_rows > 0) {
    while ($row = $result_history->fetch_assoc()) {
        $history_records[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Add jsPDF and SheetJS libraries for export functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
            --export-button-bg: #ffffff;
            --export-button-hover: #f3f4f6;
            --export-pdf-color: #dc2626;
            --export-excel-color: #059669;
            --export-print-color: #2563eb;
            --export-border-color: #e5e7eb;
            --export-text-color: #374151;
            --export-shadow: 0 1px 3px rgba(0,0,0,0.1);
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
            --export-button-bg: #1f2937;
            --export-button-hover: #374151;
            --export-pdf-color: #ef4444;
            --export-excel-color: #10b981;
            --export-print-color: #3b82f6;
            --export-border-color: #374151;
            --export-text-color: #e5e7eb;
            --export-shadow: 0 1px 3px rgba(0,0,0,0.3);
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

        .card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            border: 1px solid var(--border-color);
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .tab-button.active {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
        }

        /* Status badges */
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-extended {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-overdue {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .dark .status-active {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        
        .dark .status-extended {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        
        .dark .status-overdue {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        
        /* Export buttons */
        .export-button-group {
            display: flex;
            gap: 0.75rem;
        }
        
        .export-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            background-color: var(--export-button-bg);
            border: 1px solid var(--export-border-color);
            color: var(--export-text-color);
            box-shadow: var(--export-shadow);
        }
        
        .export-button:hover {
            transform: translateY(-2px);
        }
        
        .export-button i {
            margin-right: 0.5rem;
        }
        
        .export-button.pdf {
            color: var(--export-pdf-color);
        }
        
        .export-button.pdf:hover {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: var(--export-pdf-color);
        }
        
        .export-button.excel {
            color: var(--export-excel-color);
        }
        
        .export-button.excel:hover {
            background-color: rgba(16, 185, 129, 0.1);
            border-color: var(--export-excel-color);
        }
        
        .export-button.print {
            color: var(--export-print-color);
        }
        
        .export-button.print:hover {
            background-color: rgba(59, 130, 246, 0.1);
            border-color: var(--export-print-color);
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
                <h1 class="page-title">Sit-in Records</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search records" class="search-input" id="searchInput">
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
            <div class="card">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-xl font-bold mb-2 flex items-center">
                        <i class="fas fa-clipboard-list mr-3 text-blue-500"></i>
                        Manage Student Laboratory Sessions
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Track and manage active and historical sit-in sessions</p>
                </div>
                
                <!-- Tabs -->
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button class="tab-button active" id="current-tab" onclick="switchTab('current')">
                            <i class="fas fa-users mr-2"></i>Current Sessions
                        </button>
                        <button class="tab-button" id="history-tab" onclick="switchTab('history')">
                            <i class="fas fa-history mr-2"></i>History
                        </button>
                    </nav>
                </div>

                <!-- Current Records Tab -->
                <div id="current-records" class="tab-content p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lab</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Login Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($current_records as $record): 
                                    $login_time = strtotime($record['date'] . ' ' . $record['login_time']);
                                    $current_time = time();
                                    $duration_minutes = round(($current_time - $login_time) / 60);
                                    
                                    // Determine status and class based on duration
                                    if ($duration_minutes < 30) {
                                        $status = "Active";
                                        $status_class = "status-active";
                                    } elseif ($duration_minutes < 60) {
                                        $status = "Extended";
                                        $status_class = "status-extended";
                                    } else {
                                        $status = "Overdue";
                                        $status_class = "status-overdue";
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['student_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('h:i A', strtotime($record['login_time'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <form method="post" action="logout_student.php" class="inline" onsubmit="return confirmLogout()">
                                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="btn-primary py-1 px-3 text-xs">
                                                    <i class="fas fa-sign-out-alt mr-1"></i>Logout
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($current_records)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center">
                                            <i class="fas fa-clock text-4xl text-gray-400 dark:text-gray-500 opacity-30 mb-3"></i>
                                            <p class="text-lg font-medium">No active sessions</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">There are no students currently using the laboratory.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination Controls for Current Records -->
                        <div class="py-3 px-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4 md:mb-0">
                                    Showing <?php echo $offset + 1 ?> to <?php echo min($offset + $records_per_page, $total_current) ?> of <?php echo $total_current ?> entries
                                </div>
                                <div class="flex space-x-2 justify-center">
                                    <?php if ($total_current_pages > 1): ?>
                                        <?php for ($i = 1; $i <= $total_current_pages; $i++): ?>
                                            <a href="?page=<?php echo $i ?>&tab=current" 
                                               class="px-3 py-1 border rounded-md <?php echo $i === $current_page ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                                <?php echo $i ?>
                                            </a>
                                        <?php endfor; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Records Tab -->
                <div id="history-records" class="tab-content p-6 hidden">
                    <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                        <button onclick="confirmClearHistory()" class="btn-primary bg-red-500 hover:bg-red-600">
                            <i class="fas fa-trash mr-2"></i>Clear All History
                        </button>
                        
                        <!-- Export Buttons -->
                        <div class="export-button-group">
                            <button onclick="exportToPDF()" class="export-button pdf">
                                <i class="fas fa-file-pdf"></i>Export PDF
                            </button>
                            <button onclick="exportToExcel()" class="export-button excel">
                                <i class="fas fa-file-excel"></i>Export Excel
                            </button>
                            <button onclick="printTable()" class="export-button print">
                                <i class="fas fa-print"></i>Print
                            </button>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="history-table">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lab</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Login Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Logout Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php foreach ($history_records as $record):
                                    // Calculate duration if both times are present
                                    $duration = "";
                                    if (!empty($record['login_time']) && !empty($record['logout_time'])) {
                                        $login = new DateTime($record['login_time']);
                                        $logout = new DateTime($record['logout_time']);
                                        $interval = $login->diff($logout);
                                        $duration = $interval->format('%H:%I:%S');
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['id_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('h:i A', strtotime($record['login_time'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('h:i A', strtotime($record['logout_time'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm"><?php echo $duration; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($history_records)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center">
                                            <i class="fas fa-history text-4xl text-gray-400 dark:text-gray-500 opacity-30 mb-3"></i>
                                            <p class="text-lg font-medium">No history records</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Historical session data will appear here.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination Controls for History Records -->
                        <div class="py-3 px-6 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4 md:mb-0">
                                    Showing <?php echo $history_offset + 1 ?> to <?php echo min($history_offset + $records_per_page, $total_history) ?> of <?php echo $total_history ?> entries
                                </div>
                                <div class="flex space-x-2 justify-center">
                                    <?php if ($total_history_pages > 1): ?>
                                        <?php for ($i = 1; $i <= $total_history_pages; $i++): ?>
                                            <a href="?history_page=<?php echo $i ?>&tab=history" 
                                               class="px-3 py-1 border rounded-md <?php echo $i === $history_page ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600' ?>">
                                                <?php echo $i ?>
                                            </a>
                                        <?php endfor; ?>
                                    <?php endif; ?>
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
            const searchTerm = this.value.toLowerCase();
            const currentTab = document.querySelector('.tab-content:not(.hidden)');
            const rows = currentTab.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                let found = false;
                const cells = row.getElementsByTagName('td');
                
                for (let i = 0; i < cells.length; i++) {
                    const cellText = cells[i].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            });
        });
        
        // Check URL parameters for initial tab state
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab') || 'current';
        
        // Set initial tab state on page load
        document.addEventListener('DOMContentLoaded', function() {
            switchTab(initialTab);
        });

        function switchTab(tab) {
            const currentTab = document.getElementById('current-tab');
            const historyTab = document.getElementById('history-tab');
            const currentRecords = document.getElementById('current-records');
            const historyRecords = document.getElementById('history-records');

            // Update URL without refreshing page
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);

            if (tab === 'current') {
                currentTab.classList.add('active');
                historyTab.classList.remove('active');
                currentRecords.classList.remove('hidden');
                historyRecords.classList.add('hidden');
            } else {
                historyTab.classList.add('active');
                currentTab.classList.remove('active');
                historyRecords.classList.remove('hidden');
                currentRecords.classList.add('hidden');
            }
        }

        function confirmLogout() {
            return confirm("Are you sure you want to log out this student from the active sit-in session?");
        }

        function confirmClearHistory() {
            if (confirm("Are you sure you want to clear all history records? This action cannot be undone.")) {
                window.location.href = 'clear_history.php';
            }
        }
        
        // ...existing export functions (exportToPDF, exportToExcel, printTable)...
    </script>
</body>
</html>