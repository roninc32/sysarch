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
    $student_name = $first_name . ' ' . $middle_name . ' ' . $last_name;
    
    // Get remaining sessions from sitin_reservation table - get the most recent value
    $sessions_query = "SELECT remaining_session FROM sitin_reservation 
                      WHERE student_name='$student_name' 
                      ORDER BY id DESC LIMIT 1";
    $sessions_result = $conn->query($sessions_query);
    
    if ($sessions_result && $sessions_result->num_rows > 0) {
        $sessions_row = $sessions_result->fetch_assoc();
        $sessions_left = $sessions_row["remaining_session"];
    } else {
        // Default value if no previous records
        $sessions_left = 30;
    }
} else {
    echo "No user found.";
    exit();
}

// Get available labs - using lab numbers from existing reservations as a reference
$labs_query = "SELECT DISTINCT laboratory FROM sitin_reservation ORDER BY laboratory";
$labs_result = $conn->query($labs_query);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $purpose = $conn->real_escape_string($_POST['purpose']);
    $lab_number = $conn->real_escape_string($_POST['lab_number']);
    $time_in = $conn->real_escape_string($_POST['time_in']);
    $date = $conn->real_escape_string($_POST['date']);
    $pc_number = $conn->real_escape_string($_POST['pc_number']);
    
    // Calculate remaining sessions
    $new_remaining_sessions = $sessions_left > 0 ? $sessions_left - 1 : 0;
    
    // Insert into sitin_reservation table - now including pc_number and status
    $insert_sql = "INSERT INTO sitin_reservation (student_name, purpose, laboratory, pc_number, time_in, date, remaining_session, status) 
                  VALUES ('$student_name', '$purpose', '$lab_number', '$pc_number', '$time_in', '$date', $new_remaining_sessions, 'pending')";
    
    if ($conn->query($insert_sql) === TRUE) {
        $success_message = "Reservation successfully created!";
        
        // Update sessions_left variable for page display
        $sessions_left = $new_remaining_sessions;
    } else {
        $error_message = "Error: " . $conn->error;
    }
}

// Get PC availability information
$available_pcs = array();
try {
    $pc_status_sql = "SELECT pc_id, lab_number, is_available FROM pc_status ORDER BY lab_number, pc_id";
    $pc_status_result = $conn->query($pc_status_sql);
    
    if ($pc_status_result && $pc_status_result->num_rows > 0) {
        while ($row = $pc_status_result->fetch_assoc()) {
            $lab = $row['lab_number'];
            $pc = $row['pc_id'];
            $available = $row['is_available'] == 1;
            if (!isset($available_pcs[$lab])) {
                $available_pcs[$lab] = array();
            }
            $available_pcs[$lab][$pc] = $available;
        }
    }
} catch (Exception $e) {
    // If there's an error, continue without PC availability
}

// Get reservation status for student
$reservation_status_query = "SELECT * FROM sitin_reservation 
                            WHERE student_name='$student_name' 
                            AND status='pending' 
                            ORDER BY id DESC LIMIT 1";
$reservation_status_result = $conn->query($reservation_status_query);
$has_pending_reservation = ($reservation_status_result && $reservation_status_result->num_rows > 0);

// Fetch user's reservation history
$history_query = "SELECT * FROM sitin_reservation 
                  WHERE student_name='$student_name' 
                  ORDER BY date DESC, time_in DESC 
                  LIMIT 50";
$history_result = $conn->query($history_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Reservation</title>
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-title i {
            color: var(--accent-color);
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
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        
        .btn-outline:hover {
            background-color: var(--bg-secondary);
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

        /* Tab styling */
        .tab-container {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        
        .tab-button {
            padding: 12px 24px;
            font-weight: 500;
            color: var(--text-secondary);
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .tab-button.active {
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-disapproved {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                <a href="reservation.php" class="nav-item active">
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
    
    <!-- Main content -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Make a Reservation</h1>
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
            <!-- Welcome message -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold">Session Reservation</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage your sit-in sessions</p>
            </div>

            <!-- Status Messages -->
            <?php if (isset($success_message)): ?>
            <div class="mb-6 p-4 border-l-4 border-green-500 bg-green-50 dark:bg-green-900/20 rounded-md fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">
                            <?php echo $success_message; ?>
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

            <!-- Tab Navigation Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-calendar"></i>
                        <span>Reservation Management</span>
                    </h2>
                </div>
                
                <div class="p-0">
                    <div class="tab-container">
                        <button class="tab-button active" id="tab-new" onclick="switchTab('new')">
                            <i class="fas fa-calendar-plus mr-2"></i>New Reservation
                        </button>
                        <button class="tab-button" id="tab-history" onclick="switchTab('history')">
                            <i class="fas fa-history mr-2"></i>Reservation History
                        </button>
                    </div>
                </div>
            </div>

            <!-- New Reservation Tab -->
            <div id="content-new" class="tab-content active">
                <?php if ($sessions_left <= 0): ?>
                <div class="mb-6 p-4 border-l-4 border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20 rounded-md fade-in">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-500 text-lg"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                You have no sessions left. Please contact an administrator.
                            </p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-plus"></i>
                            Create New Reservation
                        </h3>
                        <div class="flex items-center">
                            <span class="text-sm mr-2">Sessions Left:</span>
                            <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-full text-xs font-medium">
                                <?php echo $sessions_left; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-4">
                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Purpose:</label>
                                <select id="purpose" name="purpose" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Purpose</option>
                                    <option value="C Programming">C Programming</option>
                                    <option value="Java Programming">Java Programming</option>
                                    <option value="C# Programming">C# Programming</option>
                                    <option value="ASP.NET Programming">ASP.NET Programming</option>
                                    <option value="PHP Programming">PHP Programming</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="lab_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Laboratory:</label>
                                    <select id="lab_number" name="lab_number" required onchange="updatePCList()" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Laboratory</option>
                                        <?php
                                        // Only show the specific labs requested
                                        $default_labs = ['524', '526', '528', '530', 'MAC Laboratory'];
                                        foreach ($default_labs as $lab) {
                                            echo "<option value='".$lab."'>".$lab."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="pc_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">PC Number:</label>
                                    <select id="pc_number" name="pc_number" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select PC Number</option>
                                        <?php
                                        // PCs will be populated via JavaScript based on lab selection
                                        ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date:</label>
                                    <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                
                                <div>
                                    <label for="time_in" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time In:</label>
                                    <input type="time" id="time_in" name="time_in" required 
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            
                            <div class="pt-4">
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-calendar-check mr-2"></i> Book Reservation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Reservation History Tab -->
            <div id="content-history" class="tab-content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history"></i>
                            Your Reservation History
                        </h3>
                    </div>
                    
                    <div class="p-6">
                        <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Laboratory</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PC #</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purpose</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php while ($row = $history_result->fetch_assoc()): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <?php echo date('h:i A', strtotime($row['time_in'])); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($row['laboratory']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($row['pc_number']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <?php echo htmlspecialchars($row['purpose']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            <span class="status-badge <?php echo 'status-' . $row['status']; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm">
                                            <?php echo htmlspecialchars($row['admin_comment'] ?? ''); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-xmark text-gray-400 dark:text-gray-600 text-4xl mb-4"></i>
                            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">You don't have any reservation history yet.</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Your previous reservations will appear here.</p>
                        </div>
                        <?php endif; ?>
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
        
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab content and mark button as active
            document.getElementById(`content-${tabName}`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }
        
        // Date validation
        document.getElementById('date').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (selectedDate < today) {
                alert('Please select today or a future date for your reservation.');
                this.value = '';
            }
        });
        
        // PC availability data from PHP
        const pcAvailability = <?php echo json_encode($available_pcs); ?>;
        
        // Update PC list based on selected lab
        function updatePCList() {
            const labSelect = document.getElementById('lab_number');
            const pcSelect = document.getElementById('pc_number');
            const selectedLab = labSelect.value;
            
            // Clear previous options
            pcSelect.innerHTML = '<option value="">Select PC Number</option>';
            
            // Add PCs for the selected lab
            if (selectedLab) {
                // Get available PCs for this lab if any are defined
                const labPCs = pcAvailability[selectedLab] || {};
                
                // Add all PCs, marking unavailable ones
                for (let i = 1; i <= 50; i++) {
                    const pcId = `PC ${i}`;
                    const available = labPCs[pcId] !== undefined ? labPCs[pcId] : true;
                    
                    const option = document.createElement('option');
                    option.value = pcId;
                    option.textContent = `${pcId}${!available ? ' (Unavailable)' : ''}`;
                    
                    if (!available) {
                        option.disabled = true;
                        option.style.color = 'var(--red)';
                    }
                    
                    pcSelect.appendChild(option);
                }
            }
        }
        
        // Initialize PC list when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('lab_number').value) {
                updatePCList();
            }
        });
    </script>
</body>
</html>
