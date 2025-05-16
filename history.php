<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
// Get student name for feedback
$sql_user = "SELECT first_name, last_name, profile_image, course, course_level FROM users WHERE id_number='$id_number'";
$result_user = $conn->query($sql_user);
$user_name = "";
$profile_image = 'assets/images/profile.jpg';
$course = "";
$course_level = "";
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
    $profile_image = isset($user_data["profile_image"]) && !empty($user_data["profile_image"]) ? $user_data["profile_image"] : 'assets/images/profile.jpg';
    $first_name = $user_data["first_name"];
    $course = $user_data["course"];
    $course_level = $user_data["course_level"];
}

$sql = "SELECT r.*, CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END AS has_feedback 
        FROM reservations r
        LEFT JOIN feedback f ON r.id = f.reservation_id
        WHERE r.id_number='$id_number' 
        ORDER BY r.date DESC, r.login_time DESC";
$result = $conn->query($sql);

$reservations = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }
}

// Get unique dates for the date filter
$dates = [];
foreach ($reservations as $reservation) {
    $formattedDate = date('Y-m-d', strtotime($reservation['date']));
    if (!in_array($formattedDate, $dates)) {
        $dates[] = $formattedDate;
    }
}
sort($dates); // Sort dates chronologically

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session History</title>
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
            color: var (--text-secondary);
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
        
        /* Star rating */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            cursor: pointer;
            color: #d1d5db;
            font-size: 1.75rem;
            padding: 0 0.1rem;
            transition: color 0.2s;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffb700;
        }
        
        /* Modal styling */
        .modal {
            transition: all 0.3s ease-out;
        }
        
        .modal-content {
            background-color: var(--card-bg);
            max-width: 500px;
            width: 100%;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-md);
        }
        
        .date-filter-dropdown {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            max-height: 250px;
            overflow-y: auto;
            border-radius: 0.5rem;
            z-index: 20;
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
                <a href="history.php" class="nav-item active">
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
                    <div class="user-name"><?php echo $first_name . ' ' . (isset($last_name) ? $last_name : ''); ?></div>
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
                <h1 class="page-title">Session History</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" placeholder="Search..." class="search-input">
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
            <!-- Session History Card -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-history"></i>
                        <span>Your Sit-In Sessions</span>
                    </h2>
                    <button id="filterDateBtn" class="btn btn-outline">
                        <i class="fas fa-calendar-alt mr-2"></i> Filter by Date
                    </button>
                </div>
                
                <div class="p-6">
                    <!-- Date filter dropdown -->
                    <div id="dateFilterDropdown" class="hidden absolute right-10 mt-2 w-64 rounded-lg shadow-lg z-20 date-filter-dropdown">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h3 class="font-medium text-sm">Select Date</h3>
                            <button id="clearDateFilter" class="text-xs text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">Clear Filter</button>
                        </div>
                        <div class="p-2">
                            <div class="mb-2">
                                <button id="showAllDates" class="w-full text-left px-3 py-2 rounded-md date-filter-option transition-colors text-sm">
                                    Show All Dates
                                </button>
                            </div>
                            <?php foreach ($dates as $date): ?>
                            <div class="date-option">
                                <button data-date="<?php echo $date; ?>" class="w-full text-left px-3 py-2 rounded-md date-filter-option transition-colors text-sm">
                                    <?php echo date('F d, Y (D)', strtotime($date)); ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-transparent">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lab #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Purpose</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Login Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Logout Time</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reservationTable" class="divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl mb-4 text-gray-300 dark:text-gray-600"></i>
                                                <p class="text-lg font-medium">No sit-in history found</p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Your previous lab sessions will appear here</p>
                                                <a href="reservation.php" class="btn btn-primary">
                                                    Create a New Reservation
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reservations as $index => $reservation): 
                                        $hasLogout = !empty($reservation['logout_time']) && $reservation['logout_time'] != '00:00:00';
                                        $status = $hasLogout ? 'Completed' : 'Ongoing/Incomplete';
                                        $statusClass = $hasLogout ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
                                        $reservationDate = date('Y-m-d', strtotime($reservation['date']));
                                        $hasFeedback = isset($reservation['has_feedback']) && $reservation['has_feedback'] == 1;
                                    ?>
                                        <tr class="reservation-row hover:bg-gray-50 dark:hover:bg-gray-700" data-date="<?php echo $reservationDate; ?>">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="font-medium">Lab <?php echo htmlspecialchars($reservation['lab_number']); ?></span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($reservation['sit_in_purpose']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php echo date('M d, Y', strtotime($reservation['date'])); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php echo date('h:i A', strtotime($reservation['login_time'])); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php echo $hasLogout ? date('h:i A', strtotime($reservation['logout_time'])) : 'N/A'; ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <?php if ($hasLogout): ?>
                                                    <?php if ($hasFeedback): ?>
                                                        <span class="text-gray-400 flex items-center" title="Feedback submitted">
                                                            <i class="fas fa-check-circle"></i>
                                                            <span class="ml-1 hidden sm:inline text-xs">Feedback submitted</span>
                                                        </span>
                                                    <?php else: ?>
                                                        <button onclick="openFeedbackModal(<?php echo htmlspecialchars($reservation['id']); ?>, '<?php echo htmlspecialchars($reservation['lab_number']); ?>', '<?php echo date('M d, Y', strtotime($reservation['date'])); ?>')"
                                                            class="btn btn-sm btn-outline flex items-center" title="Leave Feedback">
                                                            <i class="fas fa-comment-dots mr-1"></i> Feedback
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No results message -->
                    <div id="noResults" class="hidden py-8 text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-search text-4xl mb-4 text-gray-300 dark:text-gray-600"></i>
                            <p class="text-lg font-medium">No matching records found</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Try adjusting your search criteria</p>
                            <button id="resetSearch" class="btn btn-primary">
                                Reset Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="modal-content p-0 mx-4">
            <div class="bg-blue-600 text-white px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h3 class="text-lg font-medium">Rate Your Experience</h3>
                <button class="text-white focus:outline-none hover:text-gray-200" onclick="closeFeedbackModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="feedbackForm" class="p-6">
                <input type="hidden" id="reservation_id" name="reservation_id">
                
                <div class="mb-6">
                    <div class="text-center mb-4 pb-2 border-b border-gray-200 dark:border-gray-700">
                        <p class="font-medium">Lab #<span id="lab_number_display"></span> on <span id="session_date_display"></span></p>
                    </div>
                    
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">How was your lab experience?</label>
                        <div class="star-rating mb-2">
                            <input type="radio" id="star5" name="rating" value="5" />
                            <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                        </div>
                        <div class="text-center text-sm text-gray-500 dark:text-gray-400" id="ratingText">
                            Click to rate
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="feedback_message" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Additional comments:</label>
                        <textarea id="feedback_message" name="message" rows="3" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700"
                            placeholder="Share your thoughts about the lab facilities, equipment, etc."></textarea>
                    </div>
                    
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="issues_checkbox" name="had_issues" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="issues_checkbox" class="ml-2 block text-sm text-gray-600 dark:text-gray-400">
                            I experienced technical issues during my session
                        </label>
                    </div>
                    
                    <div id="issues_container" class="hidden mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-md">
                        <label for="issues_description" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Please describe the issues:</label>
                        <textarea id="issues_description" name="issues_description" rows="2" 
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700"
                            placeholder="Describe any technical problems you encountered..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeFeedbackModal()" 
                        class="btn btn-outline">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                    </button>
                </div>
            </form>
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

        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.reservation-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if(text.includes(searchValue)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResults = document.getElementById('noResults');
            if (visibleCount === 0 && searchValue !== '') {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        });
        
        // Reset search button
        document.getElementById('resetSearch').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            
            const rows = document.querySelectorAll('.reservation-row');
            rows.forEach(row => {
                row.style.display = '';
            });
            
            document.getElementById('noResults').classList.add('hidden');
        });
        
        // Date filter dropdown toggle
        document.getElementById('filterDateBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('dateFilterDropdown').classList.toggle('hidden');
        });
        
        // Handle date filter selection
        document.querySelectorAll('.date-option button').forEach(button => {
            button.addEventListener('click', function() {
                const selectedDate = this.getAttribute('data-date');
                filterByDate(selectedDate);
                
                // Update filter button text to show active filter
                document.getElementById('filterDateBtn').innerHTML = 
                    `<i class="fas fa-calendar-alt mr-2"></i> ${formatShortDate(selectedDate)}`;
                
                // Close dropdown
                document.getElementById('dateFilterDropdown').classList.add('hidden');
            });
        });
        
        // Show all dates option
        document.getElementById('showAllDates').addEventListener('click', function() {
            filterByDate('all');
            document.getElementById('filterDateBtn').innerHTML = 
                `<i class="fas fa-calendar-alt mr-2"></i> Filter by Date`;
            document.getElementById('dateFilterDropdown').classList.add('hidden');
        });
        
        // Clear date filter
        document.getElementById('clearDateFilter').addEventListener('click', function(e) {
            e.stopPropagation();
            filterByDate('all');
            document.getElementById('filterDateBtn').innerHTML = 
                `<i class="fas fa-calendar-alt mr-2"></i> Filter by Date`;
            document.getElementById('dateFilterDropdown').classList.add('hidden');
        });
        
        // Filter by date function
        function filterByDate(selectedDate) {
            const rows = document.querySelectorAll('.reservation-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const rowDate = row.getAttribute('data-date');
                
                if (selectedDate === 'all' || rowDate === selectedDate) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show no results message if needed
            const noResults = document.getElementById('noResults');
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
        
        // Helper function to format date for display
        function formatShortDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                day: 'numeric'
            });
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const dateDropdown = document.getElementById('dateFilterDropdown');
            const dateButton = document.getElementById('filterDateBtn');
            
            if (!dateDropdown.contains(event.target) && event.target !== dateButton) {
                dateDropdown.classList.add('hidden');
            }
        });
        
        // Feedback modal functions
        function openFeedbackModal(reservationId, labNumber, sessionDate) {
            document.getElementById('reservation_id').value = reservationId;
            document.getElementById('lab_number_display').textContent = labNumber;
            document.getElementById('session_date_display').textContent = sessionDate;
            document.getElementById('feedbackModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
        
        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.add('hidden');
            document.body.style.overflow = 'auto'; // Allow scrolling
            document.getElementById('feedbackForm').reset();
        }
        
        // Show/hide issues description based on checkbox
        document.getElementById('issues_checkbox').addEventListener('change', function() {
            const issuesContainer = document.getElementById('issues_container');
            if (this.checked) {
                issuesContainer.classList.remove('hidden');
            } else {
                issuesContainer.classList.add('hidden');
            }
        });
        
        // Star rating text update
        const ratingLabels = {
            1: "Poor - Not satisfied",
            2: "Fair - Needs improvement",
            3: "Good - Meets expectations",
            4: "Great - Very satisfied",
            5: "Excellent - Exceptional experience"
        };
        
        document.querySelectorAll('input[name="rating"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const ratingText = document.getElementById('ratingText');
                ratingText.textContent = ratingLabels[this.value];
                ratingText.classList.add('font-medium');
                
                // Set more visible color based on rating
                const ratingColors = {
                    1: 'text-red-500',
                    2: 'text-orange-500',
                    3: 'text-yellow-500',
                    4: 'text-green-500',
                    5: 'text-blue-500'
                };
                
                // Remove any previous color classes
                ratingText.className = 'text-center text-sm font-medium';
                ratingText.classList.add(ratingColors[this.value]);
            });
        });
        
        // Handle feedback submission
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Collect form data
            const reservationId = document.getElementById('reservation_id').value;
            const rating = document.querySelector('input[name="rating"]:checked')?.value || '';
            const message = document.getElementById('feedback_message').value;
            const hadIssues = document.getElementById('issues_checkbox').checked;
            const issuesDescription = document.getElementById('issues_description').value;
            
            // Validate form
            if (!rating) {
                alert('Please select a star rating');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Send data to the server using fetch API
            const formData = new FormData();
            formData.append('reservationId', reservationId);
            formData.append('rating', rating);
            formData.append('message', message);
            formData.append('hadIssues', hadIssues ? '1' : '0');
            formData.append('issuesDescription', issuesDescription);
            
            fetch('submit_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Show success message
                    alert('Thank you for your feedback!');
                    
                    // Reset form and close modal
                    closeFeedbackModal();
                    
                    // Update UI to show feedback was submitted
                    const feedbackButton = document.querySelector(`button[onclick*="openFeedbackModal(${reservationId}"]`);
                    if (feedbackButton) {
                        const parentTd = feedbackButton.parentNode;
                        parentTd.innerHTML = `
                            <span class="text-gray-400 flex items-center" title="Feedback submitted">
                                <i class="fas fa-check-circle"></i>
                                <span class="ml-1 hidden sm:inline text-xs">Feedback submitted</span>
                            </span>
                        `;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting feedback');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
    </script>
</body>
</html>
