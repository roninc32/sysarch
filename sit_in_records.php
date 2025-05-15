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
            
            /* Adding export button variables for light mode */
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
            
            /* Adding export button variables for dark mode */
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
        
        /* Redesigned export buttons */
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
        
        /* Status badges */
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--accent-green);
        }
        
        .status-extended {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--accent-yellow);
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
        <div class="card animate-fadeIn">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h1 class="text-2xl font-bold mb-2 flex items-center">
                    <i class="fas fa-clipboard-list mr-3 text-blue-500 dark:text-blue-400"></i>Sit-in Records
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-sm">Manage and view student laboratory session records</p>
            </div>
                
            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap" role="tablist">
                    <li class="mr-2">
                        <button class="tab-button inline-block p-4 border-b-2 rounded-t-lg active" 
                                onclick="switchTab('current')" id="current-tab">
                            <i class="fas fa-users mr-2"></i>Current Sessions
                        </button>
                    </li>
                    <li class="mr-2">
                        <button class="tab-button inline-block p-4 border-b-2 rounded-t-lg"
                                onclick="switchTab('history')" id="history-tab">
                            <i class="fas fa-history mr-2"></i>History
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Current Records Table -->
            <div id="current-records" class="tab-content p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Purpose</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Login Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
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
                                <tr>
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
                                        <i class="fas fa-clock text-4xl opacity-30 mb-3"></i>
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

            <!-- History Records Table -->
            <div id="history-records" class="tab-content p-6 hidden">
                <div class="mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
                    <button onclick="confirmClearHistory()" class="btn-primary bg-red-500 hover:bg-red-600 flex items-center">
                        <i class="fas fa-trash mr-2"></i>Clear All History
                    </button>
                    
                    <!-- Updated Export Buttons -->
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
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Purpose</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lab</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Login Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Logout Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Duration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
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
                                <tr>
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
                                        <i class="fas fa-history text-4xl opacity-30 mb-3"></i>
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
        
        // Enhanced Export to PDF function with better styling
        function exportToPDF() {
            // Setup jsPDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Full paths to images to ensure they load correctly
            const ucLogoPath = window.location.origin + '/sysarch/assets/images/uc-main-logo.jpg';
            const ccsLogoPath = window.location.origin + '/sysarch/assets/images/ccs-logo.png';
            
            console.log('Loading UC logo from:', ucLogoPath);
            console.log('Loading CCS logo from:', ccsLogoPath);
            
            // Load and add logos with better error handling
            const ucLogoImg = new Image();
            ucLogoImg.crossOrigin = 'Anonymous'; // Try to avoid CORS issues
            ucLogoImg.src = ucLogoPath;
            
            const ccsLogoImg = new Image();
            ccsLogoImg.crossOrigin = 'Anonymous';
            ccsLogoImg.src = ccsLogoPath;
            
            // Add title and basic info first
            doc.setFontSize(20);
            doc.setTextColor(59, 130, 246); // Blue heading
            doc.text('Sit-in Session History Report', 14, 20);
            
            doc.setFontSize(12);
            doc.setTextColor(75, 85, 99); // Gray text
            doc.text('Generated on: ' + new Date().toLocaleString(), 14, 30);
            
            // Try to add images directly without waiting
            try {
                // Increased opacity for better visibility
                doc.setGlobalAlpha(0.2); // More visible watermark
                
                // Add UC logo 
                doc.addImage(ucLogoPath, 'JPEG', 130, 10, 60, 60);
                
                // Add CCS logo
                doc.addImage(ccsLogoPath, 'PNG', 10, 180, 60, 60);
                
                doc.setGlobalAlpha(1.0); // Reset transparency
                console.log("Images added successfully to PDF");
            } catch (e) {
                console.error('Error adding logos directly:', e);
                
                // Fallback: Try to wait for images to load
                Promise.all([
                    new Promise(resolve => {
                        ucLogoImg.onload = () => {
                            console.log("UC logo loaded successfully");
                            resolve();
                        };
                        ucLogoImg.onerror = (e) => {
                            console.error("Error loading UC logo:", e);
                            resolve();
                        };
                        // Fallback if image doesn't load
                        setTimeout(resolve, 1000);
                    }),
                    new Promise(resolve => {
                        ccsLogoImg.onload = () => {
                            console.log("CCS logo loaded successfully");
                            resolve();
                        };
                        ccsLogoImg.onerror = (e) => {
                            console.error("Error loading CCS logo:", e);
                            resolve();
                        };
                        // Fallback if image doesn't load
                        setTimeout(resolve, 1000);
                    })
                ]).then(() => {
                    try {
                        // Try adding images again after load
                        doc.setGlobalAlpha(0.2);
                        doc.addImage(ucLogoImg, 'JPEG', 130, 10, 60, 60);
                        doc.addImage(ccsLogoImg, 'PNG', 10, 180, 60, 60);
                        doc.setGlobalAlpha(1.0);
                        console.log("Images added via Promise approach");
                    } catch (e) {
                        console.error('Error adding logos after wait:', e);
                    }
                    
                    // Continue with PDF generation regardless of image success
                    finalizePDF();
                });
                
                // Return early to avoid double-generation
                return;
            }
            
            // If we got here, the images were added successfully, continue with PDF
            finalizePDF();
            
            // Function to complete the PDF generation
            function finalizePDF() {
                // Add the table using autotable plugin
                doc.autoTable({ 
                    html: '#history-table',
                    startY: 40,
                    headStyles: { 
                        fillColor: [59, 130, 246], // Blue header
                        textColor: 255,
                        fontStyle: 'bold'
                    },
                    alternateRowStyles: { fillColor: [245, 250, 255] },
                    styles: {
                        lineWidth: 0.1,
                        lineColor: [220, 220, 220]
                    },
                    // Customize column widths
                    columnStyles: {
                        0: {cellWidth: 20}, // ID Number
                        1: {cellWidth: 30}, // Name
                        2: {cellWidth: 25}, // Purpose
                        6: {cellWidth: 25}, // Date
                        7: {cellWidth: 20}  // Duration
                    }
                });
                
                // Add footer
                const pageCount = doc.internal.getNumberOfPages();
                for (let i = 1; i <= pageCount; i++) {
                    doc.setPage(i);
                    doc.setFontSize(10);
                    doc.setTextColor(150, 150, 150);
                    doc.text(
                        'Page ' + i + ' of ' + pageCount,
                        doc.internal.pageSize.getWidth() / 2,
                        doc.internal.pageSize.getHeight() - 10,
                        { align: 'center' }
                    );
                }
                
                // Save the PDF
                doc.save('sit-in-history-report.pdf');
            }
        }

        // Enhanced Export to Excel function with better formatting
        function exportToExcel() {
            // Get the table data
            const table = document.getElementById('history-table');
            
            // Create workbook with custom styling
            const workbook = XLSX.utils.table_to_book(table, {
                sheet: "Sit-in History",
                dateNF: 'yyyy-mm-dd'
            });
            
            // Apply some styling (limited options in SheetJS community edition)
            const worksheet = workbook.Sheets["Sit-in History"];
            
            // Add title and note about branding
            XLSX.utils.sheet_add_aoa(worksheet, [["University of Cebu - College of Computer Studies"]], {origin: "A1"});
            XLSX.utils.sheet_add_aoa(worksheet, [["Sit-in Session History Report"]], {origin: "A2"});
            XLSX.utils.sheet_add_aoa(worksheet, [["Generated on: " + new Date().toLocaleString()]], {origin: "A3"});
            
            // Save with a custom filename
            XLSX.writeFile(workbook, 'sit-in-history-report-' + new Date().toLocaleDateString().replace(/\//g, '-') + '.xlsx');
        }
        
        // Enhanced Print function with better styling and logos
        function printTable() {
            const printContents = document.getElementById('history-records').innerHTML;
            const originalContents = document.body.innerHTML;
            
            // Get full paths to images for reliable loading
            const ucLogoPath = window.location.origin + '/sysarch/assets/images/uc-main-logo.jpg';
            const ccsLogoPath = window.location.origin + '/sysarch/assets/images/ccs-logo.png';
            
            // Create a printable version with improved styling and logos
            const printView = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Sit-in History Report</title>
                    <style>
                        @page { size: landscape; margin: 10mm; }
                        body { 
                            font-family: 'Segoe UI', Arial, sans-serif;
                            color: #333;
                            line-height: 1.6;
                            position: relative;
                        }
                        .logo-container {
                            position: relative;
                            width: 100%;
                            height: 150px;
                            margin-bottom: 20px;
                        }
                        .uc-logo {
                            position: absolute;
                            top: 0;
                            right: 30px;
                            width: 120px;
                            height: auto;
                            opacity: 0.3; /* More visible */
                        }
                        .ccs-logo {
                            position: absolute;
                            top: 0;
                            left: 30px;
                            width: 120px;
                            height: auto;
                            opacity: 0.3; /* More visible */
                        }
                        .watermark {
                            position: absolute;
                            opacity: 0.05;
                            width: 70%;
                            height: auto;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            z-index: -1;
                            pointer-events: none;
                        }
                        table { 
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        }
                        th, td { 
                            border: 1px solid #ddd;
                            padding: 12px;
                            text-align: left;
                        }
                        th { 
                            background-color: #3b82f6;
                            color: white;
                            font-weight: bold;
                            white-space: nowrap;
                        }
                        tr:nth-child(even) { background-color: #f9fafb; }
                        tr:hover { background-color: #f3f4f6; }
                        h1 { 
                            text-align: center;
                            color: #3b82f6;
                            margin-bottom: 5px;
                        }
                        .header { 
                            margin-bottom: 30px;
                            border-bottom: 2px solid #e5e7eb;
                            padding-bottom: 10px;
                            text-align: center;
                        }
                        .date {
                            text-align: center;
                            color: #6b7280;
                            font-style: italic;
                        }
                        .footer { 
                            margin-top: 30px;
                            text-align: center;
                            font-size: 12px;
                            color: #6b7280;
                            border-top: 1px solid #e5e7eb;
                            padding-top: 10px;
                        }
                        /* Hide export buttons in print */
                        .export-button-group, .btn-primary, .pagination {
                            display: none !important;
                        }
                    </style>
                </head>
                <body>
                    <!-- Logos in a container for better positioning -->
                    <div class="logo-container">
                        <img src="${ucLogoPath}" class="uc-logo" alt="UC Logo">
                        <img src="${ccsLogoPath}" class="ccs-logo" alt="CCS Logo">
                    </div>
                    
                    <!-- Also add a centered watermark for better visibility -->
                    <img src="${ucLogoPath}" class="watermark" alt="Background Logo">
                    
                    <div class="header">
                        <h1>University of Cebu - College of Computer Studies</h1>
                        <h2 style="text-align: center; margin-top: 5px;">Sit-in History Report</h2>
                        <p class="date">Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                    <table>${document.getElementById('history-table').outerHTML}</table>
                    <div class="footer">
                        <p>© ${new Date().getFullYear()} UC College of Computer Studies - Laboratory System</p>
                    </div>
                </body>
                </html>
            `;
            
            // Set the document to our printable version
            document.body.innerHTML = printView;
            
            // Print the document
            window.print();
            
            // Restore original content
            document.body.innerHTML = originalContents;
            
            // Re-attach event listeners
            document.getElementById('mobile-menu-button').addEventListener('click', function() {
                document.getElementById('mobile-menu').classList.toggle('hidden');
            });
            
            // Re-initialize dark mode toggle
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (localStorage.getItem('theme') === 'dark') {
                darkModeToggle.checked = true;
                document.documentElement.classList.add('dark');
            }
            
            darkModeToggle.addEventListener('change', function() {
                if (this.checked) {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                } else {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                }
            });
            
            // Re-initialize tab functionality
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'current';
            switchTab(tab);
        }
    </script>
</body>
</html>