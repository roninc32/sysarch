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
            --table-header-bg: #f3f4f6;
            --table-row-bg: #ffffff;
            --table-row-hover: #f9fafb;
            --table-border: #e5e7eb;
            --table-text: #111827;
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
            --table-header-bg: #374151;
            --table-row-bg: #1f2937;
            --table-row-hover: #2d3748;
            --table-border: #4b5563;
            --table-text: #f9fafb;
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
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 4px 6px var(--shadow-color);
            border: 1px solid var(--table-border);
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

        /* Tab styling */
        .tab-button {
            color: var(--text-secondary);
            border-color: transparent;
            transition: all 0.2s ease;
        }

        .tab-button.active {
            color: var(--button-primary);
            border-color: var(--button-primary);
        }

        .tab-button:hover:not(.active) {
            color: var(--text-primary);
            border-color: var(--table-border);
        }

        /* Table styling */
        table {
            border-color: var(--table-border);
        }

        table thead {
            background-color: var(--table-header-bg);
        }

        table thead th {
            color: var(--text-primary);
            font-weight: 600;
        }

        table tbody tr {
            background-color: var(--table-row-bg);
            color: var(--table-text);
        }

        table tbody tr:hover {
            background-color: var(--table-row-hover);
        }

        /* Status indicators */
        .status-active {
            background-color: rgba(16, 185, 129, 0.1);
            color: rgb(16, 185, 129);
        }

        .dark .status-active {
            background-color: rgba(16, 185, 129, 0.2);
            color: rgb(52, 211, 153);
        }

        .status-extended {
            background-color: rgba(245, 158, 11, 0.1);
            color: rgb(245, 158, 11);
        }

        .dark .status-extended {
            background-color: rgba(245, 158, 11, 0.2);
            color: rgb(251, 191, 36);
        }

        .status-overdue {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgb(239, 68, 68);
        }

        .dark .status-overdue {
            background-color: rgba(239, 68, 68, 0.2);
            color: rgb(248, 113, 113);
        }

        /* Export buttons */
        .export-button {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            font-weight: 500;
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
                    <a href="admin_logout.php" class="btn-primary flex items-center">
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
                    
                    <!-- Export Buttons -->
                    <div class="flex space-x-2">
                        <button onclick="exportToPDF()" class="export-button text-red-600 dark:text-red-400 border border-red-200 dark:border-red-900 hover:bg-red-50 dark:hover:bg-red-900/30">
                            <i class="fas fa-file-pdf mr-2"></i>PDF
                        </button>
                        <button onclick="exportToExcel()" class="export-button text-green-600 dark:text-green-400 border border-green-200 dark:border-green-900 hover:bg-green-50 dark:hover:bg-green-900/30">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </button>
                        <button onclick="printTable()" class="export-button text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900 hover:bg-blue-50 dark:hover:bg-blue-900/30">
                            <i class="fas fa-print mr-2"></i>Print
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
        
        // Export to PDF function
        function exportToPDF() {
            // Setup jsPDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Add title
            doc.setFontSize(18);
            doc.text('Sit-in Session History Report', 14, 22);
            
            // Add date
            doc.setFontSize(11);
            doc.text('Generated on: ' + new Date().toLocaleString(), 14, 30);
            
            // Add the table using autotable plugin
            doc.autoTable({ 
                html: '#history-table',
                startY: 35,
                headStyles: { fillColor: [59, 130, 246], textColor: 255 },
                alternateRowStyles: { fillColor: [240, 248, 255] }
            });
            
            // Save the PDF
            doc.save('sit-in-history-report.pdf');
        }
        
        // Export to Excel function
        function exportToExcel() {
            const table = document.getElementById('history-table');
            const workbook = XLSX.utils.table_to_book(table, {sheet: "Sit-in History"});
            XLSX.writeFile(workbook, 'sit-in-history-report.xlsx');
        }
        
        // Print function
        function printTable() {
            const printContents = document.getElementById('history-records').innerHTML;
            const originalContents = document.body.innerHTML;
            
            // Create a printable version
            const printView = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Sit-in History Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        h1 { text-align: center; }
                        .header { margin-bottom: 20px; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Sit-in History Report</h1>
                        <p style="text-align: center;">Generated on: ${new Date().toLocaleString()}</p>
                    </div>
                    <table>${document.getElementById('history-table').outerHTML}</table>
                    <div class="footer">
                        <p>&copy; ${new Date().getFullYear()} Admin Portal</p>
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
        }
    </script>
</body>
</html>