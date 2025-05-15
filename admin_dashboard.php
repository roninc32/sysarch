<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}
        
// Fetch students with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$sql_students = "SELECT * FROM users LIMIT ?, ?";
$stmt = $conn->prepare($sql_students);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result_students = $stmt->get_result();
$students = $result_students->fetch_all(MYSQLI_ASSOC);

// Fetch announcements
$sql_announcements = "SELECT * FROM announcements ORDER BY created_at DESC";
$result_announcements = $conn->query($sql_announcements);
$announcements = $result_announcements->fetch_all(MYSQLI_ASSOC);

// Fetch sit-in records with pagination (only today's active sessions)
$sql_sit_in = "SELECT * FROM active_sit_ins WHERE DATE(date) = CURDATE() LIMIT ?, ?";
$stmt = $conn->prepare($sql_sit_in);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result_sit_in = $stmt->get_result();
$sit_in_records = $result_sit_in->fetch_all(MYSQLI_ASSOC);

// Get total count of today's sit-in sessions
$sql_active_count = "SELECT COUNT(*) as active_count FROM active_sit_ins WHERE DATE(date) = CURDATE()";
$result_active_count = $conn->query($sql_active_count);
$active_count = $result_active_count->fetch_assoc()['active_count'];

// Fetch language statistics from all sit-in records
$sql_languages = "SELECT sit_in_purpose, COUNT(*) as count 
                 FROM reservations 
                 GROUP BY sit_in_purpose 
                 ORDER BY count DESC";
$result_languages = $conn->query($sql_languages);
$language_stats = $result_languages->fetch_all(MYSQLI_ASSOC);

// Convert to JSON for JavaScript
$language_data = json_encode($language_stats);


$sql_lab_numbers = "SELECT lab_number, COUNT(*) as count 
                    FROM reservations 
                    GROUP BY lab_number 
                    ORDER BY count DESC";
$result_lab_numbers = $conn->query($sql_lab_numbers);
$lab_stats = $result_lab_numbers->fetch_all(MYSQLI_ASSOC);

$lab_data = json_encode($lab_stats);

// Handle announcement creation with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_announcement'])) {
    $content = trim($_POST['content']);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    $sql_create = "INSERT INTO announcements (content) VALUES (?)";
    $stmt = $conn->prepare($sql_create);
    $stmt->bind_param("s", $content);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle announcement edit with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = (int)$_POST['id'];
    $content = trim($_POST['content']);
    $content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    
    $sql_edit = "UPDATE announcements SET content=? WHERE id=?";
    $stmt = $conn->prepare($sql_edit);
    $stmt->bind_param("si", $content, $id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle announcement deletion with prepared statements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $id = (int)$_POST['id'];
    $sql_delete = "DELETE FROM announcements WHERE id=?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: admin_dashboard.php");
    exit();
}

// Handle sit-in activity with validation & transaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $id_number = trim($_POST['id_number']);
    $purpose = trim($_POST['purpose']);
    $lab = trim($_POST['lab']);
    $sessions_left = (int)$_POST['sessions_left'] - 1;
    
    if ($sessions_left < 0) {
        header("Location: admin_dashboard.php?error=sessions");
        exit();
    }
    
    $conn->begin_transaction();
    try {
        $sql_update_sessions = "UPDATE users SET sessions_left=? WHERE id_number=?";
        $stmt = $conn->prepare($sql_update_sessions);
        $stmt->bind_param("is", $sessions_left, $id_number);
        $stmt->execute();

        $sql_insert_reservation = "INSERT INTO reservations (id_number, sit_in_purpose, lab_number, login_time, date) VALUES (?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql_insert_reservation);
        $stmt->bind_param("sss", $id_number, $purpose, $lab);
        $stmt->execute();

        $conn->commit();
        header("Location: admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: admin_dashboard.php?error=transaction");
        exit();
    }
}

// Secure Logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Dark mode variables - matching student dashboard but with improved contrast */
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
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
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
        
        /* Activity items with better visibility */
        .activity-item {
            background-color: var(--announcement-bg);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--accent-green);
            box-shadow: 0 1px 3px var(--shadow-color);
        }
        
        /* Chart container improvements */
        .chart-container {
            background-color: var(--chart-bg);
            padding: 1rem;
            border-radius: 0 0 0.5rem 0.5rem;
            height: 300px;
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

        /* Admin specific styles */
        .stat-card {
            background-color: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px var(--shadow-color);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .announcement-item {
            background-color: var(--bg-secondary);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            transition: all 0.2s;
        }
        
        .announcement-item:hover {
            box-shadow: 0 4px 6px var(--shadow-color);
        }
        
        .activity-item {
            border-left: 3px solid var(--button-primary);
            padding-left: 1rem;
            margin-bottom: 0.75rem;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Navigation Bar - Updated to match student dashboard -->
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
        <!-- Statistics Cards - Improved visibility -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-fadeIn">
            <div class="card shadow-lg">
                <div class="card-header bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-users mr-2 text-blue-500 dark:text-blue-400"></i>Total Students
                    </h3>
                </div>
                <div class="p-6 text-center">
                    <p class="stat-value text-blue-600 dark:text-blue-400"><?php echo count($students); ?></p>
                </div>
            </div>
            
            <div class="card shadow-lg">
                <div class="card-header bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-desktop mr-2 text-green-500 dark:text-green-400"></i>Active Sessions
                    </h3>
                </div>
                <div class="p-6 text-center">
                    <p class="stat-value text-green-600 dark:text-green-400"><?php echo $active_count; ?></p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">Current sit-in sessions today</p>
                </div>
            </div>
            
            <div class="card shadow-lg">
                <div class="card-header bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-bullhorn mr-2 text-yellow-500 dark:text-yellow-400"></i>Announcements
                    </h3>
                </div>
                <div class="p-6 text-center">
                    <p class="stat-value text-yellow-600 dark:text-yellow-400"><?php echo count($announcements); ?></p>
                </div>
            </div>
        </div>

        <!-- Main Content Sections - Improved visibility -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Announcements Section -->
            <section class="card shadow-lg">
                <div class="card-header flex justify-between items-center">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="fas fa-bullhorn mr-2 text-blue-500 dark:text-blue-400"></i>Announcements
                    </h2>
                    <button class="btn-primary font-medium" onclick="showAnnouncementForm()">
                        <i class="fas fa-plus mr-2"></i>New
                    </button>
                </div>

                <div class="p-6">
                    <!-- Announcement Form - Improved visibility -->
                    <form method="post" action="admin_dashboard.php" class="hidden space-y-4 mb-6 bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border border-blue-200 dark:border-blue-800" id="announcementForm">
                        <div>
                            <label for="title" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">Title</label>
                            <input type="text" id="title" name="title" required class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base" placeholder="Announcement title...">
                        </div>
                        <div>
                            <label for="content" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">Content</label>
                            <textarea id="content" name="content" required rows="4" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base" placeholder="Write your announcement here..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-medium text-gray-800 dark:text-white" onclick="showAnnouncementForm()">Cancel</button>
                            <button type="submit" name="create_announcement" class="btn-primary font-medium">
                                <i class="fas fa-paper-plane mr-2"></i>Post
                            </button>
                        </div>
                    </form>

                    <!-- Announcements List - Improved visibility -->
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-10">
                                <i class="far fa-bell-slash text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No announcements yet</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">Create your first announcement</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="mb-3">
                                        <h4 class="announcement-title"><?php echo isset($announcement['title']) ? htmlspecialchars($announcement['title']) : 'Announcement'; ?></h4>
                                        <p class="announcement-content"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    </div>
                                    <div class="flex justify-between items-center text-sm mt-4 pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <span class="font-medium text-gray-600 dark:text-gray-300"><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></span>
                                        <div class="space-x-3">
                                            <button class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors font-medium" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(addslashes($announcement['content'])); ?>')">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </button>
                                            <form method="post" action="admin_dashboard.php" class="inline">
                                                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                                <button type="submit" name="delete_announcement" class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 rounded hover:bg-red-200 dark:hover:bg-red-800 transition-colors font-medium">
                                                    <i class="fas fa-trash mr-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Recent Activities Section - Improved visibility -->
            <section class="card shadow-lg">
                <div class="card-header">
                    <h2 class="text-lg font-bold flex items-center">
                        <i class="fas fa-clipboard-check mr-2 text-green-500 dark:text-green-400"></i>Recent Activities
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php if (empty($sit_in_records)): ?>
                            <div class="text-center py-10">
                                <i class="fas fa-clipboard text-5xl opacity-50 mb-4 text-gray-400 dark:text-gray-500"></i>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">No active sessions today</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">Active sessions will appear here</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($sit_in_records as $record): ?>
                                <div class="activity-item">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-base text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($record['name']); ?></h4>
                                            <div class="flex flex-wrap gap-4 mt-2">
                                                <div class="text-sm bg-blue-50 dark:bg-blue-900/20 px-2 py-1 rounded-md">
                                                    <span class="font-medium text-blue-700 dark:text-blue-300">Lab:</span>
                                                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($record['lab_number']); ?></span>
                                                </div>
                                                <div class="text-sm bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded-md">
                                                    <span class="font-medium text-purple-700 dark:text-purple-300">Purpose:</span>
                                                    <span class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-sm bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 px-3 py-1 rounded-full font-medium">
                                            <?php echo date('g:i A', strtotime($record['login_time'])); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Charts section - Improved visibility -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card shadow-lg">
                <div class="card-header bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-200">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-indigo-500 dark:text-indigo-400"></i>Language Distribution
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="languagePieChart"></canvas>
                </div>
            </div>
            <div class="card shadow-lg">
                <div class="card-header bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200">
                    <h3 class="text-lg font-semibold flex items-center">
                        <i class="fas fa-chart-bar mr-2 text-purple-500 dark:text-purple-400"></i>Laboratory Usage
                    </h3>
                </div>
                <div class="chart-container">
                    <canvas id="laboratoryBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer - Improved visibility -->
    <footer class="mt-auto py-4 border-t border-gray-200 dark:border-gray-800 bg-white/50 dark:bg-gray-900/50">
        <div class="container mx-auto px-4">
            <div class="text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                <p>&copy; <?php echo date('Y'); ?> Admin Portal. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Add Chart.js before the existing script tag -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            
            // Force chart update on theme change
            updateChartsTheme();
        });
        
        function showAnnouncementForm() {
            const form = document.getElementById('announcementForm');
            form.classList.toggle('hidden');
        }
        
        function updateChartsTheme() {
            // Get current theme
            const isDarkMode = document.documentElement.classList.contains('dark');
            
            // Update chart theme colors
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e5e7eb' : '#111827';
            const fontSize = 14;
            
            // Update and re-render charts
            if (window.laboratoryChart) {
                window.laboratoryChart.options.scales.x.grid.color = gridColor;
                window.laboratoryChart.options.scales.y.grid.color = gridColor;
                window.laboratoryChart.options.scales.x.ticks.color = textColor;
                window.laboratoryChart.options.scales.y.ticks.color = textColor;
                window.laboratoryChart.options.scales.x.ticks.font = { size: fontSize, weight: 'bold' };
                window.laboratoryChart.options.scales.y.ticks.font = { size: fontSize, weight: 'bold' };
                window.laboratoryChart.update();
            }
            
            if (window.languageChart) {
                window.languageChart.options.plugins.legend.labels.color = textColor;
                window.languageChart.options.plugins.legend.labels.font = { size: fontSize, weight: 'bold' };
                window.languageChart.update();
            }
        }
        
        // Edit announcement with improved modal for better visibility
        function editAnnouncement(id, content) {
            // Create modal backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            backdrop.id = 'editModal';
            
            // Create modal content with better visibility
            const modal = document.createElement('div');
            modal.className = 'bg-white dark:bg-gray-800 rounded-lg p-6 max-w-lg w-full mx-4 shadow-xl border border-gray-200 dark:border-gray-700';
            modal.innerHTML = `
                <h3 class="text-xl font-bold mb-4 border-b pb-3 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">Edit Announcement</h3>
                <form id="editForm" method="POST" action="admin_dashboard.php">
                    <input type="hidden" name="id" value="${id}">
                    <div class="mb-6">
                        <label for="editContent" class="block text-sm font-semibold mb-2 text-gray-800 dark:text-gray-200">Content</label>
                        <textarea id="editContent" name="content" rows="8" 
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg 
                            bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500">${content}</textarea>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" class="px-5 py-2 border border-gray-300 dark:border-gray-600 
                            rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 font-medium text-gray-800 dark:text-white" onclick="closeEditModal()">
                            Cancel
                        </button>
                        <button type="submit" name="edit_announcement" class="bg-blue-500 hover:bg-blue-600 
                            text-white px-5 py-2 rounded-md transition-colors font-medium">
                            Save Changes
                        </button>
                    </div>
                </form>
            `;
            
            backdrop.appendChild(modal);
            document.body.appendChild(backdrop);
            
            // Add event listener to close when clicking outside
            backdrop.addEventListener('click', function(e) {
                if (e.target === backdrop) {
                    closeEditModal();
                }
            });
            
            // Focus on the textarea
            setTimeout(() => {
                document.getElementById('editContent').focus();
            }, 100);
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            if (modal) {
                document.body.removeChild(modal);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const laboratoryStats = <?php echo $lab_data; ?>;
            const languageStats = <?php echo $language_data; ?>;
            
            // Get current theme
            const isDarkMode = document.documentElement.classList.contains('dark');
            const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
            const textColor = isDarkMode ? '#e5e7eb' : '#111827';
            
            // Lab data
            const labLabels = laboratoryStats.map(item => `Lab ${item.lab_number}`);
            const labData = laboratoryStats.map(item => item.count);
            
            // Language data
            const languageLabels = languageStats.map(item => item.sit_in_purpose);
            const languageData = languageStats.map(item => item.count);
            
            // Colors with higher saturation for better visibility
            const colors = [
                '#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
                '#EC4899', '#06B6D4', '#6366F1', '#D946EF', '#F97316'
            ];
            
            // Extend colors array if needed
            const getColors = (count) => {
                if (count <= colors.length) return colors.slice(0, count);
                return Array(count).fill().map(() => `#${Math.floor(Math.random()*16777215).toString(16).padStart(6, '0')}`);
            };
            
            // Bar Chart with theme support and better visibility
            window.laboratoryChart = new Chart(document.getElementById('laboratoryBarChart'), {
                type: 'bar',
                data: {
                    labels: labLabels,
                    datasets: [{
                        label: 'Number of Sessions',
                        data: labData,
                        backgroundColor: getColors(labLabels.length),
                        borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.3)' : 'rgba(0, 0, 0, 0.3)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1F2937' : 'white',
                            titleColor: isDarkMode ? '#F9FAFB' : '#111827',
                            bodyColor: isDarkMode ? '#F3F4F6' : '#374151',
                            borderColor: isDarkMode ? '#374151' : '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            titleFont: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: gridColor,
                                lineWidth: 1
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                lineWidth: 1
                            },
                            ticks: {
                                color: textColor,
                                precision: 0,
                                stepSize: 1,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });

            // Pie Chart with theme support and better visibility
            window.languageChart = new Chart(document.getElementById('languagePieChart'), {
                type: 'pie',
                data: {
                    labels: languageLabels,
                    datasets: [{
                        data: languageData,
                        backgroundColor: getColors(languageLabels.length),
                        borderColor: isDarkMode ? '#1F2937' : '#FFFFFF',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: textColor,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                padding: 20,
                                usePointStyle: true,
                                boxWidth: 10
                            }
                        },
                        tooltip: {
                            backgroundColor: isDarkMode ? '#1F2937' : 'white',
                            titleColor: isDarkMode ? '#F9FAFB' : '#111827',
                            bodyColor: isDarkMode ? '#F3F4F6' : '#374151',
                            borderColor: isDarkMode ? '#374151' : '#E5E7EB',
                            borderWidth: 1,
                            padding: 12,
                            bodyFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            titleFont: {
                                size: 16,
                                weight: 'bold'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>