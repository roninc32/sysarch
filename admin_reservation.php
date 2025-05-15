<?php
session_start();

// Check if user is logged in and is an admin
if ((!isset($_SESSION["username"]) || !isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] != 1) && 
    !isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Handle PC status update
if (isset($_POST['update_pc_status'])) {
    $lab_number = $conn->real_escape_string($_POST['lab_number']);
    $pc_id = $conn->real_escape_string($_POST['pc_id']);
    $status = $_POST['is_available'] ? 1 : 0;
    
    // Check if PC exists in the database
    $check_sql = "SELECT * FROM pc_status WHERE lab_number='$lab_number' AND pc_id='$pc_id'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Update existing PC
        $update_sql = "UPDATE pc_status SET is_available=$status WHERE lab_number='$lab_number' AND pc_id='$pc_id'";
        $conn->query($update_sql);
    } else {
        // Insert new PC
        $insert_sql = "INSERT INTO pc_status (lab_number, pc_id, is_available) VALUES ('$lab_number', '$pc_id', $status)";
        $conn->query($insert_sql);
    }
    
    $status_message = "PC status updated successfully!";
}

// Handle reservation approval/disapproval
if (isset($_POST['update_reservation'])) {
    $reservation_id = $conn->real_escape_string($_POST['reservation_id']);
    $status = $conn->real_escape_string($_POST['status']);
    $admin_comment = $conn->real_escape_string($_POST['admin_comment']);
    
    $update_sql = "UPDATE sitin_reservation SET status='$status', admin_comment='$admin_comment', 
                  processed_date=NOW() WHERE id=$reservation_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $status_message = "Reservation status updated successfully!";
    } else {
        $error_message = "Error updating status: " . $conn->error;
    }
}

// Get available labs - Ensure all five labs are always included
$default_labs = ['524', '526', '528', '530', 'MAC Laboratory'];

// Get unique labs from database
$labs_query = "SELECT DISTINCT laboratory FROM sitin_reservation ORDER BY laboratory";
$labs_result = $conn->query($labs_query);
$db_labs = array();

if ($labs_result && $labs_result->num_rows > 0) {
    while ($row = $labs_result->fetch_assoc()) {
        $db_labs[] = $row['laboratory'];
    }
}

// Merge default labs with any additional labs from the database
$labs = array_unique(array_merge($default_labs, $db_labs));
sort($labs); // Keep them in order

// Get PC status information
$pc_status = array();
$pc_status_sql = "SELECT * FROM pc_status ORDER BY lab_number, pc_id";
$pc_status_result = $conn->query($pc_status_sql);

if ($pc_status_result && $pc_status_result->num_rows > 0) {
    while ($row = $pc_status_result->fetch_assoc()) {
        if (!isset($pc_status[$row['lab_number']])) {
            $pc_status[$row['lab_number']] = array();
        }
        $pc_status[$row['lab_number']][$row['pc_id']] = $row['is_available'];
    }
}

// Initialize empty labs with default values to ensure persistence
foreach ($labs as $lab) {
    if (!isset($pc_status[$lab])) {
        $pc_status[$lab] = array();
    }
}

// Get pending reservation requests
$pending_sql = "SELECT r.*, u.id_number FROM sitin_reservation r 
                LEFT JOIN users u ON r.student_name = CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name)  
                WHERE r.status='pending' 
                ORDER BY r.date ASC, r.time_in ASC";
$pending_result = $conn->query($pending_sql);

// Get all reservation history - Modified query with better error handling
$history_sql = "SELECT r.*, u.id_number FROM sitin_reservation r 
                LEFT JOIN users u ON r.student_name = CONCAT(u.first_name, ' ', IFNULL(u.middle_name, ''), ' ', u.last_name)  
                WHERE r.status != 'pending' 
                ORDER BY r.processed_date DESC LIMIT 100";
$history_result = $conn->query($history_sql);

// Check for SQL errors
if (!$history_result) {
    $error_message = "Error in history query: " . $conn->error;
    // If no results due to error, create empty result set to avoid PHP errors
    $history_result = new mysqli_result();
}

// Debug query - remove in production
// echo "<div style='display:none'>" . $history_sql . " | Rows: " . ($history_result ? $history_result->num_rows : 'error') . "</div>";

// Alternative query if no results and no errors
if ($history_result->num_rows == 0 && !isset($error_message)) {
    // Try a simplified query to get at least some data
    $alt_history_sql = "SELECT * FROM sitin_reservation WHERE status != 'pending' ORDER BY processed_date DESC LIMIT 100";
    $history_result = $conn->query($alt_history_sql);
}

?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reservation Management</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Color variables for light/dark mode */
        :root {
            --bg-primary: #f9fafb;
            --bg-secondary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --accent-color: #2563eb;
            --accent-hover: #1d4ed8;
            --accent-light: #dbeafe;
            --card-bg: #ffffff;
            --card-border: #e5e7eb;
            --nav-bg: #ffffff;
            --nav-border: #e5e7eb;
            --button-bg: #2563eb;
            --button-hover: #1d4ed8;
            --button-text: #ffffff;
            --input-border: #d1d5db;
            --input-bg: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --red: #ef4444;
            --green: #10b981;
            --yellow: #f59e0b;
        }

        .dark {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --accent-color: #3b82f6;
            --accent-hover: #60a5fa;
            --accent-light: #1e3a8a;
            --card-bg: #1f2937;
            --card-border: #374151;
            --nav-bg: #111827;
            --nav-border: #374151;
            --button-bg: #3b82f6;
            --button-hover: #60a5fa;
            --button-text: #ffffff;
            --input-border: #4b5563;
            --input-bg: #374151;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.4), 0 1px 2px 0 rgba(0, 0, 0, 0.2);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            --red: #f87171;
            --green: #34d399;
            --yellow: #fbbf24;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
        }
        
        nav {
            background-color: var(--nav-bg);
            border-bottom: 1px solid var(--nav-border);
        }
        
        .nav-link {
            color: var(--text-secondary);
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            color: var(--accent-color);
            background-color: var(--bg-secondary);
        }
        
        .nav-link.active {
            color: var(--accent-color);
            font-weight: 600;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--button-bg);
            color: var(--button-text);
        }
        
        .btn-primary:hover {
            background-color: var(--button-hover);
        }

        .btn-success {
            background-color: var(--green);
            color: var(--button-text);;
        }
        
        .btn-success:hover {
            background-color: #0d9668;
        }

        .btn-danger {
            background-color: var(--red);
            color: var(--button-text);
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        input, select, textarea {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
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
            background-color: var(--input-border);
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
            background-color: var(--accent-color);
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
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

        .pc-grid {
            display: grid;
            grid-template-columns: repeat(10, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .pc-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (max-width: 480px) {
            .pc-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .pc-item {
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            transition: all 0.2s;
            cursor: pointer;
        }

        .pc-available {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--green);
            border: 1px solid var(--green);
        }

        .pc-unavailable {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--red);
            border: 1px solid var(--red);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .status-approved {
            color: var(--green);
        }

        .status-disapproved {
            color: var(--red);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar -->
    <nav class="sticky top-0 z-50 px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-lg font-semibold">Admin Portal</span>
                <div class="hidden md:flex items-center ml-8 space-x-1">
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
            
            <div class="flex items-center space-x-4">
                <!-- Dark Mode Toggle -->
                <div class="flex items-center">
                    <span class="mr-2 text-sm"><i class="fas fa-sun"></i></span>
                    <label class="toggle-switch">
                        <input type="checkbox" id="darkModeToggle">
                        <span class="toggle-slider"></span>
                    </label>
                    <span class="ml-2 text-sm"><i class="fas fa-moon"></i></span>
                </div>
                
                <!-- Logout Button -->
                <a href="logout.php" class="btn btn-primary bg-red-500 hover:bg-red-600 hidden md:flex">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-button" class="md:hidden p-2 rounded-md focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div id="mobile-menu" class="md:hidden hidden mt-2 pb-2">
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
            <a href="logout.php" class="nav-link block mb-1 text-red-600 dark:text-red-400">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6 flex-grow">
        <div class="mb-6 fade-in">
            <h1 class="text-2xl font-bold">Reservation Management</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Manage PC availability and student reservation requests</p>
        </div>

        <?php if (isset($status_message)): ?>
            <div class="mb-6 p-4 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 rounded-md fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            <?php echo $status_message; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="mb-6 p-4 border-l-4 border-red-500 bg-red-50 dark:bg-red-900/20 rounded-md fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">
                            <?php echo $error_message; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
            <nav class="flex -mb-px" aria-label="Tabs">
                <button class="tab-button active" id="tab-btn-requests" onclick="switchTab('requests')">
                    <i class="fas fa-list-alt mr-2"></i> Reservation Requests
                </button>
                <button class="tab-button" id="tab-btn-pc" onclick="switchTab('pc')">
                    <i class="fas fa-desktop mr-2"></i> PC Availability
                </button>
                <button class="tab-button" id="tab-btn-history" onclick="switchTab('history')">
                    <i class="fas fa-history mr-2"></i> Reservation Logs
                </button>
            </nav>
        </div>

        <!-- Reservation Requests Tab -->
        <div id="tab-requests" class="tab-content fade-in">
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold"><i class="fas fa-clipboard-list text-blue-500 mr-2"></i> Pending Requests</h2>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full text-xs font-medium">
                            <?php echo $pending_result ? $pending_result->num_rows : 0; ?> Pending
                        </span>
                    </div>

                    <?php if ($pending_result && $pending_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID Number</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laboratory</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PC Number</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php while ($row = $pending_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['id_number']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['student_name']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['laboratory']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['pc_number']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['purpose']; ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <button onclick="showApproveModal(<?php echo $row['id']; ?>)" class="btn btn-success py-1 px-2 text-xs mr-1">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button onclick="showDisapproveModal(<?php echo $row['id']; ?>)" class="btn btn-danger py-1 px-2 text-xs">
                                                    <i class="fas fa-times"></i> Disapprove
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-clipboard-check text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500 dark:text-gray-400">No pending reservation requests.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PC Availability Tab -->
        <div id="tab-pc" class="tab-content hidden fade-in">
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold"><i class="fas fa-desktop text-blue-500 mr-2"></i> PC Availability Control</h2>
                    </div>

                    <div class="mb-6">
                        <label for="lab-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Laboratory:</label>
                        <select id="lab-select" class="w-full md:w-1/3 mb-4" onchange="showPCs()">
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?php echo $lab; ?>"><?php echo $lab; ?></option>
                            <?php endforeach; ?>
                        </select>

                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium" id="selected-lab-title"></h3>
                            <div>
                                <button onclick="setAllPCs(true)" class="btn btn-success py-1 px-2 text-sm mr-2">
                                    <i class="fas fa-check-circle mr-1"></i> Set All Available
                                </button>
                                <button onclick="setAllPCs(false)" class="btn btn-danger py-1 px-2 text-sm">
                                    <i class="fas fa-times-circle mr-1"></i> Set All Unavailable
                                </button>
                            </div>
                        </div>

                        <div class="pc-grid" id="pc-container">
                            <!-- PCs will be populated here by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservation History Tab -->
        <div id="tab-history" class="tab-content hidden fade-in">
            <div class="card">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold"><i class="fas fa-history text-blue-500 mr-2"></i> Reservation Logs</h2>
                    </div>

                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID Number</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Student Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laboratory</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PC Number</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comment</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php while ($row = $history_result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['id_number']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['student_name']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo date('h:i A', strtotime($row['time_in'])); ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['laboratory']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['pc_number']; ?></td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['purpose']; ?></td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="<?php echo $row['status'] == 'approved' ? 'status-approved' : 'status-disapproved'; ?> font-medium">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm"><?php echo $row['admin_comment']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-history text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500 dark:text-gray-400">No reservation history found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approve-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Approve Reservation</h3>
            <form id="approve-form" method="POST">
                <input type="hidden" name="update_reservation" value="1">
                <input type="hidden" id="approve-id" name="reservation_id" value="">
                <input type="hidden" name="status" value="approved">
                
                <div class="mb-4">
                    <label for="approve-comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Admin Comment (Optional):</label>
                    <textarea id="approve-comment" name="admin_comment" rows="3" class="w-full"></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('approve-modal')" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-2"></i> Confirm Approval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Disapprove Modal -->
    <div id="disapprove-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-semibold mb-4">Disapprove Reservation</h3>
            <form id="disapprove-form" method="POST">
                <input type="hidden" name="update_reservation" value="1">
                <input type="hidden" id="disapprove-id" name="reservation_id" value="">
                <input type="hidden" name="status" value="disapproved">
                
                <div class="mb-4">
                    <label for="disapprove-comment" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Reason for Disapproval:</label>
                    <textarea id="disapprove-comment" name="admin_comment" rows="3" class="w-full" required></textarea>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal('disapprove-modal')" class="btn bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-2"></i> Confirm Disapproval
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-8 py-6 border-t border-gray-200 dark:border-gray-800">
        <div class="container mx-auto px-4">
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    &copy; <?php echo date('Y'); ?> Admin Portal | College of Computer Studies
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    Version 2.0
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
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
        
        // Tab switching
        function switchTab(tabName) {
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-button');
            
            tabs.forEach(tab => {
                tab.classList.add('hidden');
            });
            
            buttons.forEach(button => {
                button.classList.remove('active');
            });
            
            document.getElementById('tab-' + tabName).classList.remove('hidden');
            document.getElementById('tab-btn-' + tabName).classList.add('active');
            
            // If PC tab is selected, refresh PC display
            if (tabName === 'pc') {
                showPCs();
            }
        }
        
        // PC Availability data from PHP
        const pcStatus = <?php echo json_encode($pc_status); ?>;
        
        // Show PCs for selected lab
        function showPCs() {
            const labSelect = document.getElementById('lab-select');
            const selectedLab = labSelect.value;
            const pcContainer = document.getElementById('pc-container');
            const labTitle = document.getElementById('selected-lab-title');
            
            labTitle.textContent = `Laboratory ${selectedLab} - PC Status`;
            
            // Clear previous content
            pcContainer.innerHTML = '';
            
            // Add PCs
            for (let i = 1; i <= 50; i++) {
                const pcId = `PC ${i}`;
                
                // Check if PC status exists in our data
                let isAvailable = true; // Default to available
                
                // If we have data for this lab and this specific PC
                if (pcStatus[selectedLab] && pcId in pcStatus[selectedLab]) {
                    // Convert to proper boolean (0 = false, anything else = true)
                    isAvailable = pcStatus[selectedLab][pcId] == 1;
                }
                
                const pcElement = document.createElement('div');
                pcElement.className = `pc-item ${isAvailable ? 'pc-available' : 'pc-unavailable'}`;
                pcElement.dataset.pc = pcId;
                pcElement.dataset.lab = selectedLab;
                pcElement.dataset.available = isAvailable ? '1' : '0';
                pcElement.onclick = function() { togglePCStatus(this); };
                
                pcElement.innerHTML = `
                    <div class="font-bold">${pcId}</div>
                    <div class="text-xs mt-1">${isAvailable ? 'Available' : 'Unavailable'}</div>
                `;
                
                pcContainer.appendChild(pcElement);
            }
            
            // For debugging - can be removed in production
            console.log("PC Status data for " + selectedLab + ":", pcStatus[selectedLab]);
        }
        
        // Toggle PC availability status
        function togglePCStatus(element) {
            const pcId = element.dataset.pc;
            const lab = element.dataset.lab;
            const currentStatus = element.dataset.available === '1';
            const newStatus = !currentStatus;
            
            // Send AJAX request to update PC status
            const formData = new FormData();
            formData.append('update_pc_status', '1');
            formData.append('lab_number', lab);
            formData.append('pc_id', pcId);
            formData.append('is_available', newStatus ? '1' : '0');
            
            fetch('admin_reservation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Update UI without waiting for page reload
                element.className = `pc-item ${newStatus ? 'pc-available' : 'pc-unavailable'}`;
                element.dataset.available = newStatus ? '1' : '0';
                element.querySelector('.text-xs').textContent = newStatus ? 'Available' : 'Unavailable';
                
                // Update pcStatus object to match database
                if (!pcStatus[lab]) pcStatus[lab] = {};
                pcStatus[lab][pcId] = newStatus ? 1 : 0;
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Set all PCs in current lab to given status
        function setAllPCs(available) {
            const labSelect = document.getElementById('lab-select');
            const selectedLab = labSelect.value;
            const pcElements = document.querySelectorAll('.pc-item');
            
            pcElements.forEach(element => {
                const pcId = element.dataset.pc;
                
                // Send AJAX request to update PC status
                const formData = new FormData();
                formData.append('update_pc_status', '1');
                formData.append('lab_number', selectedLab);
                formData.append('pc_id', pcId);
                formData.append('is_available', available ? '1' : '0');
                
                fetch('admin_reservation.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // Update UI
                    element.className = `pc-item ${available ? 'pc-available' : 'pc-unavailable'}`;
                    element.dataset.available = available ? '1' : '0';
                    element.querySelector('.text-xs').textContent = available ? 'Available' : 'Unavailable';
                    
                    // Update pcStatus object
                    if (!pcStatus[selectedLab]) pcStatus[selectedLab] = {};
                    pcStatus[selectedLab][pcId] = available;
                })
                .catch(error => console.error('Error:', error));
            });
        }
        
        // Modal functions
        function showApproveModal(id) {
            document.getElementById('approve-id').value = id;
            document.getElementById('approve-modal').classList.remove('hidden');
        }
        
        function showDisapproveModal(id) {
            document.getElementById('disapprove-id').value = id;
            document.getElementById('disapprove-modal').classList.remove('hidden');
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }
        
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Show PCs on initial load
            if (document.getElementById('tab-pc')) {
                showPCs();
            }
        });
    </script>
</body>
</html>
