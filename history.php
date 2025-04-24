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

$sql = "SELECT * FROM reservations WHERE id_number='$id_number' ORDER BY date DESC, login_time DESC";
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
        /* Dark mode variables */
        :root {
            --bg-primary: #f0f9ff;
            --bg-secondary: #dbeafe;
            --text-primary: #1f2937;
            --text-secondary: #4b5563;
            --card-bg: #eff6ff;
            --card-header: #bfdbfe;
            --nav-bg: #ffffff;
            --nav-text: #111827;
            --nav-hover-bg: #374151;
            --nav-hover-text: #ffffff;
            --button-primary: #3b82f6;
            --button-hover: #2563eb;
            --button-text: #ffffff;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --table-header-bg: #e5e7eb;
            --table-bg: #ffffff;
            --table-border: #e5e7eb;
            --table-row-hover: #f3f4f6;
            --modal-bg: #ffffff;
            --modal-header: #3b82f6;
            --modal-text: #1f2937;
            --modal-input-bg: #ffffff;
            --modal-input-border: #d1d5db;
            --modal-input-text: #111827;
            --date-filter-text: #111827;
            --date-filter-hover: #f3f4f6;
            --date-option-text: #4b5563;
            --form-label: #4b5563;
            --star-default: #d1d5db;
            --star-active: #ffb700;
        }

        .dark {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --card-bg: #1f2937;
            --card-header: #374151;
            --nav-bg: #111827;
            --nav-text: #f9fafb;
            --nav-hover-bg: #60a5fa;
            --nav-hover-text: #111827;
            --button-primary: #3b82f6;
            --button-hover: #60a5fa;
            --button-text: #f9fafb;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --table-header-bg: #374151;
            --table-bg: #1f2937;
            --table-border: #4b5563;
            --table-row-hover: #2d3748;
            --modal-bg: #1f2937;
            --modal-header: #3b82f6;
            --modal-text: #f3f4f6;
            --modal-input-bg: #374151;
            --modal-input-border: #4b5563;
            --modal-input-text: #f9fafb;
            --date-filter-text: #e5e7eb;
            --date-filter-hover: #374151;
            --date-option-text: #d1d5db;
            --form-label: #d1d5db;
            --star-default: #4b5563;
            --star-active: #ffb700;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
        }

        nav {
            background-color: var(--nav-bg);
            box-shadow: 0 2px 4px var(--shadow-color);
        }

        .nav-link {
            color: var(--nav-text);
        }

        .nav-link:hover {
            background-color: var(--nav-hover-bg);
            color: var(--nav-hover-text);
        }

        .card {
            background-color: var(--card-bg);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px var(--shadow-color);
        }

        .card-header {
            background-color: var(--card-header);
        }

        .btn-primary {
            background-color: var(--button-primary);
            color: var(--button-text);
        }

        .btn-primary:hover {
            background-color: var(--button-hover);
        }

        /* Table styling */
        table {
            border-color: var(--table-border);
        }

        table thead {
            background-color: var(--table-header-bg);
        }

        table tbody tr {
            background-color: var(--table-bg);
        }

        table tbody tr:hover {
            background-color: var(--table-row-hover);
        }
        
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
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
            width: 35px;
            height: 35px;
            margin: 0 5px;
            font-size: 35px;
            color: #ccc;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffb700;
        }

        /* Modal styling */
        .modal {
            transition: opacity 0.25s ease;
        }

        .modal-content {
            background-color: var(--modal-bg);
            color: var(--modal-text);
        }
        
        .modal-header {
            background-color: var(--modal-header);
        }
        
        /* Form styling improvements */
        .form-label {
            color: var(--form-label);
            font-weight: 500;
        }
        
        .form-input, .form-textarea {
            background-color: var(--modal-input-bg);
            border-color: var(--modal-input-border);
            color: var(--modal-input-text);
        }
        
        .form-input:focus, .form-textarea:focus {
            border-color: var(--button-primary);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
        }

        /* Star rating - improved visibility */
        .star-rating label {
            color: var(--star-default);
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: var(--star-active);
        }
        
        /* Date filter improvements */
        .date-filter-dropdown {
            max-height: 250px;
            overflow-y: auto;
            scrollbar-width: thin;
            background-color: var(--modal-bg);
            color: var(--modal-text);
            border-color: var(--modal-input-border);
        }
        
        .date-filter-dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        .date-filter-dropdown::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        .date-filter-dropdown::-webkit-scrollbar-thumb {
            background-color: var(--button-primary);
            border-radius: 20px;
        }
        
        .date-filter-option {
            color: var(--date-filter-text);
        }
        
        .date-filter-option:hover {
            background-color: var(--date-filter-hover);
        }
        
        .date-option-text {
            color: var(--date-option-text);
        }
        
        /* Additional helper classes */
        .text-visibility-enhanced {
            text-shadow: 0 0 1px rgba(0,0,0,0.1);
        }
        
        .dark .text-visibility-enhanced {
            text-shadow: 0 0 1px rgba(255,255,255,0.1);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <nav class="sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                    <button type="button"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                        aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
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
                        <span class="text-xl font-bold hidden lg:block">Student Portal</span>
                    </div>
                    <div class="hidden sm:block sm:ml-6">
                        <div class="flex space-x-4">
                            <a href="dashboard.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-home mr-2"></i> Home
                            </a>
                            <a href="edit_student_info.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-user-edit mr-2"></i> Profile
                            </a>
                            <a href="history.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center bg-blue-500 text-white">
                                <i class="fas fa-history mr-2"></i> History
                            </a>
                            <a href="reservation.php"
                                class="nav-link px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-calendar-alt mr-2"></i> Reservation
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
                    
                    <!-- Notifications -->
                    <div class="relative">
                        <button
                            class="p-1 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
                            id="notifications-menu" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">View notifications</span>
                            <i class="fas fa-bell text-lg"></i>
                        </button>
                        <!-- Dropdown content -->
                        <div class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg py-1 bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 hidden notifications-dropdown"
                            role="menu" aria-orientation="vertical" aria-labelledby="notifications-menu">
                            <div class="px-4 py-2 text-center border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium">Notifications</p>
                            </div>
                            <div class="px-4 py-2 text-sm text-center text-gray-500 dark:text-gray-400">
                                No new notifications
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="edit_student_info.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-user-edit mr-2"></i> Profile
                </a>
                <a href="history.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium bg-blue-500 text-white">
                    <i class="fas fa-history mr-2"></i> History
                </a>
                <a href="reservation.php"
                    class="nav-link block px-3 py-2 rounded-md text-base font-medium">
                    <i class="fas fa-calendar-alt mr-2"></i> Reservation
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-4 flex-grow">
        <div class="max-w-7xl mx-auto">
            <div class="card rounded-lg shadow-lg overflow-hidden animate-fadeIn">
                <div class="card-header p-6 sticky top-0 z-10">
                    <h1 class="text-xl md:text-2xl font-bold text-center flex items-center justify-center">
                        <i class="fas fa-history mr-3"></i>Sit-in History
                    </h1>
                </div>
                
                <div class="p-6">
                    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                        <div class="w-full md:w-1/2">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                                    <i class="fas fa-search text-gray-400"></i>
                                </span>
                                <input type="text" id="searchInput" 
                                    class="search-input w-full pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:ring-2 border" 
                                    placeholder="Search by lab, purpose, date...">
                            </div>
                        </div>
                        <div class="relative">
                            <button id="filterDateBtn" class="flex items-center px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <i class="fas fa-calendar-alt mr-2"></i> Filter by Date
                            </button>
                            <!-- Date filter dropdown -->
                            <div id="dateFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-20 border border-gray-200 dark:border-gray-700 date-filter-dropdown">
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
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Lab #</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b hidden md:table-cell">Purpose</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Date</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b hidden md:table-cell">Login Time</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b hidden md:table-cell">Logout Time</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Status</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium border-b">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="reservationTable">
                                <?php if (empty($reservations)): ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center empty-state">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl mb-4 opacity-40"></i>
                                                <p class="text-lg font-medium">No sit-in history found</p>
                                                <p class="text-sm">Your previous lab sessions will appear here</p>
                                                <a href="reservation.php" class="mt-4 btn-primary px-4 py-2 rounded-md">
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
                                    ?>
                                        <tr class="reservation-row" data-date="<?php echo $reservationDate; ?>">
                                            <td class="py-3 px-4 border-b">
                                                <span class="font-medium">Lab <?php echo htmlspecialchars($reservation['lab_number']); ?></span>
                                            </td>
                                            <td class="py-3 px-4 border-b hidden md:table-cell">
                                                <?php echo htmlspecialchars($reservation['sit_in_purpose']); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b">
                                                <?php echo date('M d, Y', strtotime($reservation['date'])); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b hidden md:table-cell">
                                                <?php echo date('h:i A', strtotime($reservation['login_time'])); ?>
                                            </td>
                                            <td class="py-3 px-4 border-b hidden md:table-cell">
                                                <?php echo $hasLogout ? date('h:i A', strtotime($reservation['logout_time'])) : 'N/A'; ?>
                                            </td>
                                            <td class="py-3 px-4 border-b">
                                                <span class="px-2 py-1 rounded-full text-xs <?php echo $statusClass; ?>">
                                                    <?php echo $status; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 border-b">
                                                <?php if ($hasLogout && !isset($reservation['has_feedback'])): ?>
                                                <button onclick="openFeedbackModal(<?php echo htmlspecialchars($reservation['id']); ?>, '<?php echo htmlspecialchars($reservation['lab_number']); ?>', '<?php echo date('M d, Y', strtotime($reservation['date'])); ?>')"
                                                    class="text-green-500 hover:text-green-700">
                                                    <i class="fas fa-comment-dots"></i>
                                                    <span class="sr-only">Leave Feedback</span>
                                                </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- No results message -->
                    <div id="noResults" class="hidden py-8 text-center empty-state">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-search text-4xl mb-4 opacity-40"></i>
                            <p class="text-lg font-medium">No matching records found</p>
                            <p class="text-sm">Try adjusting your search criteria</p>
                            <button id="resetSearch" class="mt-4 btn-primary px-4 py-2 rounded-md">
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
        <div class="modal-content rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
            <div class="modal-header px-6 py-4 text-white flex justify-between items-center">
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
                        <label class="form-label block text-sm mb-2">How was your lab experience?</label>
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
                        <label for="feedback_message" class="form-label block text-sm mb-2">Additional comments:</label>
                        <textarea id="feedback_message" name="feedback_message" rows="3" 
                            class="form-textarea w-full px-3 py-2 rounded-md shadow-sm"
                            placeholder="Share your thoughts about the lab facilities, equipment, etc."></textarea>
                    </div>
                    
                    <div class="flex items-center mb-4">
                        <input type="checkbox" id="issues_checkbox" name="had_issues" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="issues_checkbox" class="ml-2 block text-sm">
                            I experienced technical issues during my session
                        </label>
                    </div>
                    
                    <div id="issues_container" class="hidden mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-md">
                        <label for="issues_description" class="form-label block text-sm mb-2">Please describe the issues:</label>
                        <textarea id="issues_description" name="issues_description" rows="2" 
                            class="form-textarea w-full px-3 py-2 rounded-md shadow-sm"
                            placeholder="Describe any technical problems you encountered..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeFeedbackModal()" 
                        class="px-4 py-2 rounded-md bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="btn-primary px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="mt-auto py-4">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> Student Portal. All rights reserved.</p>
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
        
        // Notifications toggle
        document.getElementById('notifications-menu').addEventListener('click', function() {
            document.querySelector('.notifications-dropdown').classList.toggle('hidden');
        });
        
        // Close dropdowns when clicking outside
        window.addEventListener('click', function(event) {
            const notificationsMenu = document.getElementById('notifications-menu');
            const notificationsDropdown = document.querySelector('.notifications-dropdown');
            
            if (!notificationsMenu.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
            }
        });
        
        // Search functionality - updated for better matching
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
                    `<i class="fas fa-calendar-alt mr-2"></i> <span class="text-visibility-enhanced">${formatShortDate(selectedDate)}</span>`;
                
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
            
            // Existing notification dropdown handling
            const notificationsMenu = document.getElementById('notifications-menu');
            const notificationsDropdown = document.querySelector('.notifications-dropdown');
            
            if (!notificationsMenu.contains(event.target) && !notificationsDropdown.contains(event.target)) {
                notificationsDropdown.classList.add('hidden');
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
                    1: 'text-red-600 dark:text-red-400',
                    2: 'text-orange-600 dark:text-orange-400',
                    3: 'text-yellow-600 dark:text-yellow-400',
                    4: 'text-green-600 dark:text-green-400',
                    5: 'text-blue-600 dark:text-blue-400'
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
                // Improved alert using a custom notification
                showNotification('Please select a star rating', 'warning');
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
            formData.append('hadIssues', hadIssues ? '1' : '0'); // Convert boolean to string
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
                    showNotification('Thank you for your feedback!', 'success');
                    
                    // Reset form and close modal
                    closeFeedbackModal();
                    
                    // Update UI to show feedback was submitted
                    const feedbackButtons = document.querySelectorAll(`button[onclick*="openFeedbackModal(${reservationId}"]`);
                    feedbackButtons.forEach(btn => {
                        btn.innerHTML = '<i class="fas fa-check-circle"></i>';
                        btn.title = 'Feedback submitted';
                        btn.disabled = true;
                        btn.classList.remove('text-green-500', 'hover:text-green-700');
                        btn.classList.add('text-gray-400');
                    });
                } else {
                    showNotification('Error: ' + data.message, 'warning');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while submitting feedback', 'warning');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            });
        });
        
        // Custom notification function (for better UX than alert)
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            
            // Set appropriate styles based on type
            let bgColor, textColor, icon;
            switch(type) {
                case 'success':
                    bgColor = 'bg-green-100 dark:bg-green-900/30';
                    textColor = 'text-green-800 dark:text-green-200';
                    icon = 'fa-check-circle';
                    break;
                case 'warning':
                    bgColor = 'bg-yellow-100 dark:bg-yellow-900/30';
                    textColor = 'text-yellow-800 dark:text-yellow-200';
                    icon = 'fa-exclamation-triangle';
                    break;
                default:
                    bgColor = 'bg-blue-100 dark:bg-blue-900/30';
                    textColor = 'text-blue-800 dark:text-blue-200';
                    icon = 'fa-info-circle';
            }
            
            // Apply styles to notification
            notification.className = `fixed top-4 right-4 z-50 ${bgColor} ${textColor} px-6 py-4 rounded-md shadow-lg flex items-center transform transition-all duration-500 translate-y-[-20px] opacity-0`;
            notification.innerHTML = `
                <i class="fas ${icon} mr-3 text-lg"></i>
                <p class="font-medium">${message}</p>
            `;
            
            // Add to DOM
            document.body.appendChild(notification);
            
            // Trigger animation
            setTimeout(() => {
                notification.style.transform = 'translateY(0)';
                notification.style.opacity = '1';
            }, 10);
            
            // Remove after delay
            setTimeout(() => {
                notification.style.transform = 'translateY(-20px)';
                notification.style.opacity = '0';
                
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 3000);
        }
    </script>
</body>
</html>
