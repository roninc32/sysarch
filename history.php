<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];
// Get student name for feedback
$sql_user = "SELECT first_name, last_name FROM users WHERE id_number='$id_number'";
$result_user = $conn->query($sql_user);
$user_name = "";
if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];
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
    <title>Reservation History</title>
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
            --table-header-bg: #f3f4f6;
            --table-bg: #ffffff;
            --table-border: #e5e7eb;
            --table-row-hover: #f9fafb;
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
            --table-header-bg: #374151;
            --table-bg: #1f2937;
            --table-border: #374151;
            --table-row-hover: #2d3748;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Simple card design */
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
        
        /* Clean navigation */
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
        
        /* Button styles */
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
        
        /* Toggle switch */
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
        
        /* Table styling */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        table thead {
            background-color: var(--table-header-bg);
        }
        
        table th {
            font-weight: 600;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--table-border);
        }
        
        table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--table-border);
            vertical-align: middle;
        }
        
        table tbody tr {
            background-color: var(--table-bg);
            transition: background-color 0.2s;
        }
        
        table tbody tr:hover {
            background-color: var(--table-row-hover);
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
            color: var(--star-default, #d1d5db);
            font-size: 1.75rem;
            padding: 0 0.1rem;
            transition: color 0.2s;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: var(--star-active, #ffb700);
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
        
        /* Search & filter items */
        .search-input {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
        }
        
        /* Date filter dropdown */
        .date-filter-dropdown {
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
            max-height: 250px;
            overflow-y: auto;
            border-radius: 0.5rem;
        }
        
        .date-filter-option {
            transition: background-color 0.2s;
            color: var(--text-primary);
        }
        
        .date-filter-option:hover {
            background-color: var(--bg-secondary);
        }
        
        /* Simple fade in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-screen flex flex-col">
    <!-- Simple Navigation Bar -->
    <nav class="sticky top-0 z-50 px-4 py-2">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center">
                <span class="text-lg font-semibold hidden md:block">Student Portal</span>
                <div class="hidden md:flex items-center ml-8 space-x-1">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="edit_student_info.php" class="nav-link">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="history.php" class="nav-link active">
                        <i class="fas fa-history mr-2"></i> History
                    </a>
                    <a href="reservation.php" class="nav-link">
                        <i class="fas fa-calendar mr-2"></i> Reservation
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
            <a href="dashboard.php" class="nav-link block mb-1">
                <i class="fas fa-home mr-2"></i> Home
            </a>
            <a href="edit_student_info.php" class="nav-link block mb-1">
                <i class="fas fa-user mr-2"></i> Profile
            </a>
            <a href="history.php" class="nav-link block mb-1 active">
                <i class="fas fa-history mr-2"></i> History
            </a>
            <a href="reservation.php" class="nav-link block mb-1">
                <i class="fas fa-calendar mr-2"></i> Reservation
            </a>
            <!-- Logout Button in mobile menu -->
            <a href="logout.php" class="nav-link block mb-1 text-red-600 dark:text-red-400">
                <i class="fas fa-sign-out-alt mr-2"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-6 flex-grow">
        <div class="mb-6 fade-in">
            <h1 class="text-2xl font-bold">Reservation History</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">View your past sit-in sessions</p>
        </div>
        
        <div class="card fade-in">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <div class="w-full md:w-1/2">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="fas fa-search text-gray-400"></i>
                            </span>
                            <input type="text" id="searchInput" 
                                class="search-input w-full pl-10 pr-4 py-2 border rounded-md shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                placeholder="Search by lab, purpose, date...">
                        </div>
                    </div>
                    <div class="relative">
                        <button id="filterDateBtn" class="flex items-center px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <i class="fas fa-calendar-alt mr-2"></i> Filter by Date
                        </button>
                        <!-- Date filter dropdown -->
                        <div id="dateFilterDropdown" class="hidden absolute right-0 mt-2 w-64 rounded-lg shadow-lg z-20 date-filter-dropdown">
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
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg">
                    <table class="min-w-full border rounded-lg overflow-hidden">
                        <thead>
                            <tr>
                                <th>Lab #</th>
                                <th class="hidden md:table-cell">Purpose</th>
                                <th>Date</th>
                                <th class="hidden md:table-cell">Login Time</th>
                                <th class="hidden md:table-cell">Logout Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="reservationTable">
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
                                    <tr class="reservation-row" data-date="<?php echo $reservationDate; ?>">
                                        <td>
                                            <span class="font-medium">Lab <?php echo htmlspecialchars($reservation['lab_number']); ?></span>
                                        </td>
                                        <td class="hidden md:table-cell">
                                            <?php echo htmlspecialchars($reservation['sit_in_purpose']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($reservation['date'])); ?>
                                        </td>
                                        <td class="hidden md:table-cell">
                                            <?php echo date('h:i A', strtotime($reservation['login_time'])); ?>
                                        </td>
                                        <td class="hidden md:table-cell">
                                            <?php echo $hasLogout ? date('h:i A', strtotime($reservation['logout_time'])) : 'N/A'; ?>
                                        </td>
                                        <td>
                                            <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($hasLogout): ?>
                                                <?php if ($hasFeedback): ?>
                                                    <span class="text-gray-400 flex items-center" title="Feedback submitted">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span class="ml-1 hidden sm:inline text-xs">Feedback submitted</span>
                                                    </span>
                                                <?php else: ?>
                                                    <button onclick="openFeedbackModal(<?php echo htmlspecialchars($reservation['id']); ?>, '<?php echo htmlspecialchars($reservation['lab_number']); ?>', '<?php echo date('M d, Y', strtotime($reservation['date'])); ?>')"
                                                        class="text-blue-500 hover:text-blue-700 flex items-center" title="Leave Feedback">
                                                        <i class="fas fa-comment-dots"></i>
                                                        <span class="ml-1 hidden sm:inline text-xs">Submit feedback</span>
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
                        class="px-4 py-2 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-500">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
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
                    &copy; <?php echo date('Y'); ?> Student Portal | College of Computer Studies
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
