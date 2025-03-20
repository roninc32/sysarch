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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-container {
            @apply bg-gradient-to-r from-indigo-600 to-blue-500 shadow-lg;
        }
        
        .nav-link {
            @apply px-4 py-2 text-white hover:text-white/90 font-medium transition-all duration-200
                relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 
                after:bg-white after:transition-all after:duration-200 hover:after:w-full;
        }
        
        .nav-link.active {
            @apply text-white after:w-full font-bold;
        }
        
        .logout-btn {
            @apply px-4 py-2 text-white border-2 border-white/80 rounded-lg 
                hover:bg-white hover:text-indigo-600 transition-all duration-200
                font-medium focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2
                focus:ring-offset-indigo-600;
        }

        .nav-brand {
            @apply flex items-center space-x-3 text-white;
        }

        .nav-brand-text {
            @apply text-lg font-bold hidden md:block;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col" style="background-image: url('assets/images/bg.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <header class="w-full top-0 z-50">
        <nav class="nav-container">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="nav-brand">
                            <img class="h-10 w-auto" src="assets/images/ccs-logo.png" alt="CCS Logo">
                        </div>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-4">
                                <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-home mr-2"></i>Dashboard
                                </a>
                                <a href="student_record.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-users mr-2"></i>Students
                                </a>
                                <a href="sit_in_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-clipboard-list mr-2"></i>Sit-in Records
                                </a>
                                <a href="search_student.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                    <i class="fas fa-search mr-2"></i>Search Student
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <a href="#" onclick="confirmAdminLogout()" class="logout-btn">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white/80 backdrop-blur-md shadow-lg rounded-lg overflow-hidden">
            <div class="p-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Sit-in Records</h1>
                
                <!-- Tabs -->
                <div class="mb-4 border-b border-gray-200">
                    <ul class="flex flex-wrap -mb-px" role="tablist">
                        <li class="mr-2">
                            <button class="inline-block p-4 text-blue-600 border-b-2 border-blue-600 rounded-t-lg active" 
                                    onclick="switchTab('current')" id="current-tab">
                                Current Records
                            </button>
                        </li>
                        <li class="mr-2">
                            <button class="inline-block p-4 text-gray-500 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300"
                                    onclick="switchTab('history')" id="history-tab">
                                History
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Current Records Table -->
                <div id="current-records" class="tab-content">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($current_records as $record): 
                                    $login_time = strtotime($record['date'] . ' ' . $record['login_time']);
                                    $current_time = time();
                                    $duration_minutes = round(($current_time - $login_time) / 60);
                                    
                                    // Determine status and color based on duration
                                    if ($duration_minutes < 30) {
                                        $status = "Active";
                                        $status_color = "green";
                                    } elseif ($duration_minutes < 60) {
                                        $status = "Extended";
                                        $status_color = "yellow";
                                    } else {
                                        $status = "Overdue";
                                        $status_color = "red";
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['student_id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['login_time']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['date']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?php echo $status_color; ?>-100 text-<?php echo $status_color; ?>-800">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <form method="post" action="logout_student.php" class="inline" onsubmit="return confirmLogout()">
                                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm transition duration-150">Logout</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- Pagination Controls for Current Records -->
                        <div class="px-6 py-4 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <?php echo $offset + 1 ?> to <?php echo min($offset + $records_per_page, $total_current) ?> of <?php echo $total_current ?> entries
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($total_current_pages > 1): ?>
                                        <?php for ($i = 1; $i <= $total_current_pages; $i++): ?>
                                            <a href="?page=<?php echo $i ?>&tab=current" 
                                               class="px-3 py-1 border rounded-md <?php echo $i === $current_page ? 'bg-blue-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
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
                <div id="history-records" class="tab-content hidden">
                    <div class="mb-4">
                        <button onclick="confirmClearHistory()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition duration-150">
                            Clear History
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lab</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logout Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($history_records as $record): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['id_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['login_time']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['logout_time']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($record['date']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <!-- Pagination Controls for History Records -->
                        <div class="px-6 py-4 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <?php echo $history_offset + 1 ?> to <?php echo min($history_offset + $records_per_page, $total_history) ?> of <?php echo $total_history ?> entries
                                </div>
                                <div class="flex space-x-2">
                                    <?php if ($total_history_pages > 1): ?>
                                        <?php for ($i = 1; $i <= $total_history_pages; $i++): ?>
                                            <a href="?history_page=<?php echo $i ?>&tab=history" 
                                               class="px-3 py-1 border rounded-md <?php echo $i === $history_page ? 'bg-blue-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' ?>">
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
    </main>

    <script>
        // Add this function before the existing script code
        function confirmLogout() {
            return confirm("Are you sure you want to log out this student from the active sit-in session?");
        }

        // Add this function at the beginning of the script section
        function confirmAdminLogout() {
            if (confirm("Are you sure you want to logout from your admin account?")) {
                window.location.href = 'admin_logout.php';
            }
        }

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
                currentTab.classList.add('text-blue-600', 'border-blue-600');
                currentTab.classList.remove('text-gray-500', 'border-transparent');
                historyTab.classList.remove('text-blue-600', 'border-blue-600');
                historyTab.classList.add('text-gray-500', 'border-transparent');
                currentRecords.classList.remove('hidden');
                historyRecords.classList.add('hidden');
            } else {
                historyTab.classList.add('text-blue-600', 'border-blue-600');
                historyTab.classList.remove('text-gray-500', 'border-transparent');
                currentTab.classList.remove('text-blue-600', 'border-blue-600');
                currentTab.classList.add('text-gray-500', 'border-transparent');
                historyRecords.classList.remove('hidden');
                currentRecords.classList.add('hidden');
            }
        }

        function confirmClearHistory() {
            if (confirm("Are you sure you want to clear all history records? This action cannot be undone.")) {
                window.location.href = 'clear_history.php';
            }
        }
    </script>

    <footer class="mt-auto">
        <div class="bg-white/80 backdrop-blur-md">
            <div class="container mx-auto px-4 py-6">
                <p class="text-center text-gray-600 text-sm">&copy; <?php echo date("Y"); ?> All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>