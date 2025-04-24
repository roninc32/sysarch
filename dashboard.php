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
} else {
    echo "No user found.";
    exit();
}

$sql_sessions = "SELECT sessions_left FROM users WHERE id_number='$id_number'";
$result_sessions = $conn->query($sql_sessions);

if ($result_sessions->num_rows > 0) {
    $row_sessions = $result_sessions->fetch_assoc();
    $sessions_left = $row_sessions["sessions_left"];
} else {
    $sessions_left = "N/A";
}

// Fetch announcements
$sql_announcements = "SELECT * FROM announcements ORDER BY created_at DESC";
$result_announcements = $conn->query($sql_announcements);
$announcements = [];
if ($result_announcements->num_rows > 0) {
    while ($row = $result_announcements->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        /* Better visibility for announcements */
        .announcement-item {
            background-color: var(--announcement-bg);
            color: var(--announcement-text);
            border-radius: 0.5rem;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent-blue);
            box-shadow: 0 1px 3px var(--shadow-color);
        }
        
        .announcement-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .announcement-content {
            font-size: 1rem;
            color: var(--text-primary);
            white-space: pre-wrap;
        }
        
        /* Custom animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-out forwards;
        }

        /* Student info card styling */
        .student-info-card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.3s;
        }

        .profile-image-container img {
            border: 4px solid var(--border-color);
            transition: border-color 0.3s;
        }

        /* Rules section styling */
        .rules-section {
            max-height: 600px;
            overflow-y: auto;
            border-radius: 0.5rem;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }

        .rules-header {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: var(--card-header);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .rules-content {
            padding: 1.5rem;
        }

        .rules-content ul {
            margin-left: 1.5rem;
        }

        .rules-content li {
            margin-bottom: 0.75rem;
        }

        /* Improved scrollbars */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--button-hover);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar - Matched with admin dashboard -->
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
                        <span class="text-xl font-bold hidden lg:block">Student Portal</span>
                    </div>
                    <div class="hidden sm:block sm:ml-6">
                        <div class="flex space-x-4">
                            <a href="dashboard.php"
                                class="nav-link text-sm font-medium active">
                                <i class="fas fa-home mr-2"></i> Home
                            </a>
                            <a href="edit_student_info.php"
                                class="nav-link text-sm font-medium">
                                <i class="fas fa-user-edit mr-2"></i> Profile
                            </a>
                            <a href="history.php"
                                class="nav-link text-sm font-medium">
                                <i class="fas fa-history mr-2"></i> History
                            </a>
                            <a href="reservation.php"
                                class="nav-link text-sm font-medium">
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
                            class="btn-primary flex items-center"
                            id="notifications-menu" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-bell mr-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="sm:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="dashboard.php"
                    class="nav-link block active">
                    <i class="fas fa-home mr-2"></i> Home
                </a>
                <a href="edit_student_info.php"
                    class="nav-link block">
                    <i class="fas fa-user-edit mr-2"></i> Profile
                </a>
                <a href="history.php"
                    class="nav-link block">
                    <i class="fas fa-history mr-2"></i> History
                </a>
                <a href="reservation.php"
                    class="nav-link block">
                    <i class="fas fa-calendar-alt mr-2"></i> Reservation
                </a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 flex-grow">
        <!-- Page Header -->
        <div class="mb-6 animate-fadeIn">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white">Student Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-300">Welcome back, <?php echo $first_name; ?>!</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 animate-fadeIn">
            <!-- Student Information - Enhanced & Decluttered -->
            <div class="md:col-span-3">
                <div class="card shadow-lg student-info-card h-full">
                    <div class="card-header bg-blue-100 dark:bg-blue-900/30 flex items-center">
                        <i class="fas fa-user-circle mr-2 text-blue-600 dark:text-blue-400"></i>
                        <h2 class="text-lg font-bold">Student Profile</h2>
                    </div>
                    <div class="p-4">
                        <!-- Simplified profile section with better spacing -->
                        <div class="profile-image-container flex justify-center mb-4">
                            <img src="<?php echo $profile_image; ?>" alt="Profile Image" 
                                class="w-28 h-28 rounded-full object-cover shadow-md border-4 border-white dark:border-gray-700">
                        </div>
                        
                        <div class="text-center mb-3">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo $first_name . ' ' . $last_name; ?></h3>
                            <p class="text-blue-600 dark:text-blue-400"><?php echo $course; ?></p>
                        </div>
                        
                        <!-- Sessions counter with improved visibility -->
                        <div class="flex justify-center mb-4">
                            <div class="bg-blue-50 dark:bg-blue-900/40 rounded-full px-4 py-1 inline-flex items-center">
                                <i class="fas fa-calendar-check text-blue-500 dark:text-blue-300 mr-2"></i>
                                <span class="font-bold text-gray-900 dark:text-white"><?php echo $sessions_left; ?></span>
                                <span class="text-gray-600 dark:text-gray-300 ml-1 text-sm">Sessions Left</span>
                            </div>
                        </div>
                        
                        <!-- Student info with cleaner layout -->
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 mb-4">
                            <div class="grid grid-cols-1 gap-2">
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-id-card text-blue-500 dark:text-blue-400 mr-2 w-5 text-center"></i>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 w-20">ID:</span>
                                    <span class="text-gray-900 dark:text-white"><?php echo $id_number; ?></span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-graduation-cap text-blue-500 dark:text-blue-400 mr-2 w-5 text-center"></i>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 w-20">Level:</span>
                                    <span class="text-gray-900 dark:text-white"><?php echo $course_level; ?></span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-envelope text-blue-500 dark:text-blue-400 mr-2 w-5 text-center"></i>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 w-20">Email:</span>
                                    <span class="text-gray-900 dark:text-white text-xs truncate"><?php echo $email; ?></span>
                                </div>
                                <div class="flex items-center text-sm">
                                    <i class="fas fa-map-marker-alt text-blue-500 dark:text-blue-400 mr-2 w-5 text-center"></i>
                                    <span class="font-medium text-gray-700 dark:text-gray-300 w-20">Address:</span>
                                    <span class="text-gray-900 dark:text-white text-xs truncate"><?php echo $address; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action buttons with better spacing -->
                        <div class="space-y-2">
                            <a href="edit_student_info.php" class="btn-primary w-full py-2 flex justify-center items-center bg-blue-500 hover:bg-blue-600 text-sm">
                                <i class="fas fa-user-edit mr-2"></i>Edit Profile
                            </a>
                            <a href="logout.php" class="btn-primary w-full py-2 flex justify-center items-center bg-red-500 hover:bg-red-600 text-sm">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements - Enhanced for dark mode visibility -->
            <div class="md:col-span-6">
                <div class="card shadow-lg h-full">
                    <div class="card-header bg-yellow-100 dark:bg-yellow-900/50 flex items-center">
                        <i class="fas fa-bullhorn mr-2 text-yellow-600 dark:text-yellow-300"></i>
                        <h2 class="text-lg font-bold">Announcements</h2>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3 max-h-[520px] overflow-y-auto custom-scrollbar pr-2">
                            <?php if (empty($announcements)): ?>
                                <div class="text-center py-8 bg-gray-50 dark:bg-gray-800/70 rounded-lg">
                                    <i class="far fa-bell-slash text-5xl opacity-50 mb-3 text-gray-400 dark:text-gray-300"></i>
                                    <p class="text-lg font-semibold">No new announcements.</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Check back later for updates.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement-item transform transition-all duration-300 hover:-translate-y-1 border border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center px-4 py-3 bg-gray-50 dark:bg-gray-800 rounded-t-lg border-b border-gray-200 dark:border-gray-700">
                                            <div class="bg-yellow-100 dark:bg-yellow-700 p-2 rounded-full mr-3">
                                                <i class="fas fa-bullhorn text-yellow-600 dark:text-yellow-200"></i>
                                            </div>
                                            <h4 class="text-base font-bold text-gray-900 dark:text-white">
                                                <?php echo isset($announcement['title']) ? htmlspecialchars($announcement['title']) : 'Announcement'; ?>
                                            </h4>
                                        </div>
                                        <div class="p-4 bg-white dark:bg-gray-750">
                                            <div class="announcement-content bg-gray-50 dark:bg-gray-800/80 p-3 rounded-lg text-gray-800 dark:text-gray-100">
                                                <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                            </div>
                                            <div class="flex justify-between items-center text-sm mt-3">
                                                <span class="font-medium text-gray-600 dark:text-gray-300 flex items-center">
                                                    <i class="fas fa-user-shield mr-2 text-blue-500 dark:text-blue-400"></i>CCS Admin
                                                </span>
                                                <span class="text-gray-500 dark:text-gray-400 flex items-center bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full">
                                                    <i class="far fa-calendar-alt mr-1"></i><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?php echo count($announcements); ?> announcement(s)
                            </span>
                            <a href="#" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm flex items-center">
                                View All <i class="fas fa-chevron-right ml-1 text-xs"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rules and Regulations - Enhanced scrolling -->
            <div class="md:col-span-3">
                <div class="card shadow-lg h-full flex flex-col">
                    <div class="card-header bg-green-100 dark:bg-green-900/50 flex items-center flex-shrink-0">
                        <i class="fas fa-clipboard-list mr-2 text-green-600 dark:text-green-300"></i>
                        <h2 class="text-lg font-bold">Lab Rules</h2>
                    </div>
                    <div class="p-4 overflow-hidden flex flex-col flex-grow">
                        <!-- Banner Section -->
                        <div class="bg-green-50 dark:bg-green-900/30 rounded-lg p-3 mb-3 flex-shrink-0">
                            <h3 class="text-base font-bold text-green-800 dark:text-green-300 flex items-center">
                                <i class="fas fa-exclamation-circle mr-2"></i>Laboratory Guidelines
                            </h3>
                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                Please observe these rules to maintain a productive lab environment.
                            </p>
                        </div>
                        
                        <!-- Scrollable Rules Content -->
                        <div class="overflow-y-auto custom-scrollbar pr-2 flex-grow">
                            <div class="space-y-3">
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center text-sm">
                                        <i class="fas fa-volume-mute text-red-500 mr-2"></i>Proper Conduct
                                    </h4>
                                    <ul class="list-disc pl-5 text-gray-700 dark:text-gray-300 space-y-1 text-xs">
                                        <li>Maintain silence and proper decorum inside the laboratory</li>
                                        <li>Switch off mobile phones and other personal equipment</li>
                                        <li>No games allowed (computer games, card games, etc.)</li>
                                    </ul>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center text-sm">
                                        <i class="fas fa-laptop text-blue-500 mr-2"></i>Computer Usage
                                    </h4>
                                    <ul class="list-disc pl-5 text-gray-700 dark:text-gray-300 space-y-1 text-xs">
                                        <li>Internet use requires instructor permission</li>
                                        <li>Downloading and installing software is prohibited</li>
                                        <li>Do not access non-course related websites</li>
                                        <li>Do not delete files or change computer settings</li>
                                        <li>Maximum 15-minute usage, then yield to others</li>
                                    </ul>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center text-sm">
                                        <i class="fas fa-door-open text-purple-500 mr-2"></i>Lab Protocols
                                    </h4>
                                    <ol class="list-decimal pl-5 text-gray-700 dark:text-gray-300 space-y-1 text-xs">
                                        <li>Do not enter unless the instructor is present</li>
                                        <li>Deposit bags and knapsacks at the counter</li>
                                        <li>Follow your instructor's seating arrangement</li>
                                        <li>Close all programs at the end of class</li>
                                        <li>Return chairs to their proper places</li>
                                    </ol>
                                </div>
                                
                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                                    <h4 class="font-bold text-gray-900 dark:text-white mb-2 flex items-center text-sm">
                                        <i class="fas fa-ban text-red-500 mr-2"></i>Prohibited Actions
                                    </h4>
                                    <ul class="list-disc pl-5 text-gray-700 dark:text-gray-300 space-y-1 text-xs">
                                        <li>No chewing gum, eating, drinking, or smoking</li>
                                        <li>No vandalism of any kind</li>
                                        <li>No disruptive behavior or hostile actions</li>
                                        <li>No public display of physical intimacy</li>
                                    </ul>
                                </div>
                                
                                <div class="bg-red-50 dark:bg-red-900/30 p-3 rounded-lg">
                                    <h4 class="font-bold text-red-800 dark:text-red-300 mb-1 text-sm">Disciplinary Action</h4>
                                    <p class="text-xs text-gray-700 dark:text-gray-300"><strong>First Offense:</strong> Suspension recommendation</p>
                                    <p class="text-xs text-gray-700 dark:text-gray-300"><strong>Subsequent Offenses:</strong> Heavier sanctions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex-shrink-0">
                        <div class="text-center">
                            <a href="#" class="text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 font-medium text-sm">
                                <i class="fas fa-download mr-1"></i> Complete Rules
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section Removed -->
    </div>

    <!-- Footer - Matching admin dashboard -->
    <footer class="mt-auto py-4 border-t border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> Student Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        /* Additional styles for enhanced UI */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: var(--accent-blue);
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--button-hover);
        }
        
        /* Card hover effects */
        .card {
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1), 0 6px 6px rgba(0,0,0,0.08);
        }
        
        /* Enhanced dark mode for announcements */
        .dark .announcement-content {
            background-color: rgba(31, 41, 55, 0.8); /* Darker background for better contrast */
            color: #f3f4f6; /* Lighter text for better readability */
        }
        
        /* Special dark mode background for better visibility */
        .dark .bg-gray-750 {
            background-color: #1a2334; /* Custom darker shade */
        }
        
        /* Ensure rules section scrolls properly */
        .flex-col {
            display: flex;
            flex-direction: column;
        }
        
        .flex-grow {
            flex-grow: 1;
        }
        
        .flex-shrink-0 {
            flex-shrink: 0;
        }
        
        .overflow-hidden {
            overflow: hidden;
        }
    </style>

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

        // Enhanced interaction for announcements
        document.addEventListener('DOMContentLoaded', function() {
            const announcements = document.querySelectorAll('.announcement-item');
            
            announcements.forEach(function(announcement, index) {
                // Add staggered animation delay
                announcement.style.animationDelay = (index * 0.15) + 's';
                announcement.classList.add('animate-fadeIn');
            });
            
            // Add notification count badge if needed
            const notificationBadge = document.createElement('span');
            if (announcements.length > 0) {
                const notificationsButton = document.getElementById('notifications-menu');
                notificationBadge.className = 'absolute -top-1 -right-1 bg-red-500 text-xs text-white w-5 h-5 flex items-center justify-center rounded-full';
                notificationBadge.textContent = announcements.length;
                notificationsButton.appendChild(notificationBadge);
                notificationsButton.classList.add('relative');
            }
        });
    </script>
</body>

</html>