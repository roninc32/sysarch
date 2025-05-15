<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];

// Handle point to session conversion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['convert_points'])) {
    // Get current points
    $fetch_points_sql = "SELECT points FROM users WHERE id_number = ?";
    $stmt = $conn->prepare($fetch_points_sql);
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $current_points = $user_data['points'];
    
    $points_to_convert = (int)$_POST['points_to_convert'];
    
    // Check if points are valid (multiple of 3)
    if ($points_to_convert % 3 != 0) {
        echo "<script>alert('Points must be in multiples of 3');</script>";
    }
    // Check if user has enough points
    elseif ($points_to_convert > $current_points) {
        echo "<script>alert('You do not have enough points to convert');</script>";
    }
    else {
        $sessions_to_add = $points_to_convert / 3;
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Deduct points
            $deduct_points_sql = "UPDATE users SET points = points - ? WHERE id_number = ?";
            $stmt = $conn->prepare($deduct_points_sql);
            $stmt->bind_param("is", $points_to_convert, $id_number);
            $stmt->execute();
            
            // Add sessions
            $add_sessions_sql = "UPDATE users SET sessions_left = sessions_left + ? WHERE id_number = ?";
            $stmt = $conn->prepare($add_sessions_sql);
            $stmt->bind_param("is", $sessions_to_add, $id_number);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            echo "<script>alert('Successfully converted " . $points_to_convert . " points to " . $sessions_to_add . " sessions!');</script>";
            echo "<script>window.location.href = 'dashboard.php';</script>";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            echo "<script>alert('Error converting points: " . $e->getMessage() . "');</script>";
        }
    }
}

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
    $points = isset($row["points"]) ? $row["points"] : 0;
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

// Fetch leaderboard data (top 10 students by points)
$sql_leaderboard = "SELECT id_number, first_name, last_name, points 
                   FROM users 
                   ORDER BY points DESC 
                   LIMIT 10";
$result_leaderboard = $conn->query($sql_leaderboard);
$leaderboard = [];
if ($result_leaderboard->num_rows > 0) {
    while ($row = $result_leaderboard->fetch_assoc()) {
        $leaderboard[] = $row;
    }
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
        
        .btn-outline {
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            background-color: transparent;
        }
        
        .btn-outline:hover {
            background-color: var(--accent-light);
            color: var(--accent-color);
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
        
        /* Badge styles */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-blue {
            background-color: var(--accent-light);
            color: var(--accent-color);
        }
        
        /* Announcement redesign */
        .announcement {
            border-left: 4px solid var(--accent-color);
            padding-left: 1rem;
            margin-bottom: 1.5rem;
            position: relative;
            transition: all 0.2s;
        }
        
        .announcement:hover {
            transform: translateX(5px);
        }
        
        .announcement-date {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }
        
        .announcement-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .announcement-content {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.5;
            white-space: pre-line;
        }
        
        /* Stat card styles */
        .stat-card {
            padding: 1rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
            color: white;
        }
        
        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        /* Clean leaderboard */
        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            background-color: var(--card-bg);
            border: 1px solid var(--card-border);
        }
        
        .leaderboard-rank {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            margin-right: 0.75rem;
            border-radius: 50%;
        }
        
        .rank-1 {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .rank-2 {
            background-color: #e5e7eb;
            color: #4b5563;
        }
        
        .rank-3 {
            background-color: #fed7aa;
            color: #9a3412;
        }
        
        .leaderboard-name {
            flex-grow: 1;
            font-weight: 500;
        }
        
        .leaderboard-points {
            font-weight: 600;
            color: var(--accent-color);
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--input-border);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }
        
        /* Section header */
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .section-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .section-header .icon {
            margin-right: 0.5rem;
            color: var(--accent-color);
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
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home mr-2"></i> Home
                    </a>
                    <a href="edit_student_info.php" class="nav-link">
                        <i class="fas fa-user mr-2"></i> Profile
                    </a>
                    <a href="history.php" class="nav-link">
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
                
                <!-- Logout Button - Added here -->
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
            <a href="dashboard.php" class="nav-link block mb-1 active">
                <i class="fas fa-home mr-2"></i> Home
            </a>
            <a href="edit_student_info.php" class="nav-link block mb-1">
                <i class="fas fa-user mr-2"></i> Profile
            </a>
            <a href="history.php" class="nav-link block mb-1">
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
        <!-- Welcome Section -->
        <div class="mb-6 fade-in">
            <h1 class="text-2xl font-bold">Welcome, <?php echo $first_name; ?>!</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Here's your dashboard overview</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Profile & Stats -->
            <div class="lg:col-span-3 space-y-6 fade-in" style="animation-delay: 0.1s;">
                <!-- Profile Card -->
                <div class="card p-6">
                    <div class="flex justify-center mb-4">
                        <img src="<?php echo $profile_image; ?>" alt="Profile Image" 
                            class="w-24 h-24 rounded-full object-cover border-2 border-blue-500">
                    </div>
                    
                    <div class="text-center mb-4">
                        <h3 class="text-lg font-semibold"><?php echo $first_name . ' ' . $last_name; ?></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo $course; ?></p>
                        <div class="mt-2 text-xs py-1 px-3 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 inline-block rounded-full">
                            <?php echo $course_level; ?>
                        </div>
                    </div>
                    
                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <!-- Sessions counter -->
                        <div class="stat-card">
                            <div class="stat-icon bg-blue-500">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $sessions_left; ?></div>
                                <div class="stat-label">Sessions Left</div>
                            </div>
                        </div>
                        
                        <!-- Points counter -->
                        <div class="stat-card">
                            <div class="stat-icon bg-yellow-500">
                                <i class="fas fa-star"></i>
                            </div>
                            <div>
                                <div class="stat-value"><?php echo $points; ?></div>
                                <div class="stat-label">Points</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Convert Points Section -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg mb-4">
                        <h4 class="font-medium text-sm mb-3 flex items-center">
                            <i class="fas fa-exchange-alt text-blue-500 mr-2"></i> Convert Points to Sessions
                        </h4>
                        <form method="post" onsubmit="return confirmPointConversion()">
                            <div class="flex items-center gap-2 mb-2">
                                <input type="number" name="points_to_convert" min="3" step="3" max="<?php echo $points; ?>" required 
                                       class="w-full p-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                                <button type="submit" name="convert_points" class="btn btn-primary whitespace-nowrap">
                                    Convert
                                </button>
                            </div>
                            <div class="text-xs text-center text-gray-600 dark:text-gray-400">
                                <span class="font-medium">3 points</span> = <span class="font-medium">1 session</span>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Actions - Modified to remove logout button -->
                    <div class="space-y-2">
                        <a href="edit_student_info.php" class="btn btn-outline w-full">
                            <i class="fas fa-user-edit mr-2"></i> Edit Profile
                        </a>
                    </div>
                </div>
                
                <!-- Student Info Card -->
                <div class="card p-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-id-card icon"></i>
                        <h2>Personal Info</h2>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">ID Number:</span>
                            <span class="font-medium"><?php echo $id_number; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Email:</span>
                            <span class="font-medium"><?php echo $email; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Address:</span>
                            <span class="font-medium"><?php echo $address; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Middle Column: Announcements -->
            <div class="lg:col-span-5 space-y-6 fade-in" style="animation-delay: 0.2s;">
                <!-- Announcements Card -->
                <div class="card">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="section-header">
                            <i class="fas fa-bullhorn icon"></i>
                            <h2>Announcements</h2>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-10">
                                <div class="inline-block p-3 bg-gray-100 dark:bg-gray-700 rounded-full mb-3">
                                    <i class="fas fa-bell-slash text-gray-400 text-3xl"></i>
                                </div>
                                <p class="font-medium">No announcements available</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Check back later for updates</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-6 max-h-[500px] overflow-y-auto pr-2">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="announcement">
                                        <div class="announcement-date">
                                            <?php echo date('F j, Y', strtotime($announcement['created_at'])); ?>
                                        </div>
                                        <div class="announcement-title">
                                            <?php echo !empty($announcement['title']) ? htmlspecialchars($announcement['title']) : 'Important Announcement'; ?>
                                        </div>
                                        <div class="announcement-content">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                        </div>
                                        <div class="flex justify-end mt-3">
                                            <span class="badge badge-blue">
                                                <i class="fas fa-user-shield mr-1"></i> Admin
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Actions Card -->
                <div class="card p-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-bolt icon"></i>
                        <h2>Quick Actions</h2>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <a href="reservation.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus mr-2"></i> New Reservation
                        </a>
                        <a href="history.php" class="btn btn-outline">
                            <i class="fas fa-history mr-2"></i> View History
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Leaderboard -->
            <div class="lg:col-span-4 space-y-6 fade-in" style="animation-delay: 0.3s;">
                <!-- Leaderboard Card -->
                <div class="card">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="section-header">
                            <i class="fas fa-trophy icon"></i>
                            <h2>Leaderboard</h2>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <?php if (empty($leaderboard)): ?>
                            <div class="text-center py-10">
                                <div class="inline-block p-3 bg-gray-100 dark:bg-gray-700 rounded-full mb-3">
                                    <i class="fas fa-users-slash text-gray-400 text-3xl"></i>
                                </div>
                                <p class="font-medium">No leaderboard data</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Check back later</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-2 max-h-[350px] overflow-y-auto pr-2">
                                <?php foreach ($leaderboard as $index => $student): ?>
                                    <div class="leaderboard-item <?php echo $student['id_number'] == $id_number ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700' : ''; ?>">
                                        <div class="leaderboard-rank <?php 
                                            if ($index === 0) echo 'rank-1'; 
                                            elseif ($index === 1) echo 'rank-2';
                                            elseif ($index === 2) echo 'rank-3';
                                            else echo 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300';
                                        ?>">
                                            <?php echo $index + 1; ?>
                                        </div>
                                        <div class="leaderboard-name">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            <?php if ($student['id_number'] == $id_number): ?>
                                                <span class="ml-2 text-xs badge badge-blue">You</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="leaderboard-points">
                                            <?php echo htmlspecialchars($student['points']); ?> pts
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Lab Rules Card -->
                <div class="card">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="section-header">
                            <i class="fas fa-clipboard-list icon"></i>
                            <h2>Lab Rules</h2>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="rule-item">
                                <h4 class="font-medium flex items-center mb-2">
                                    <i class="fas fa-volume-mute text-red-500 mr-2"></i>
                                    Silence in the Lab
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Maintain silence and proper decorum inside the laboratory at all times.
                                </p>
                            </div>
                            
                            <div class="rule-item">
                                <h4 class="font-medium flex items-center mb-2">
                                    <i class="fas fa-mobile-alt text-yellow-500 mr-2"></i>
                                    No Mobile Phones
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Switch off or silence mobile phones and other personal devices.
                                </p>
                            </div>
                            
                            <div class="rule-item">
                                <h4 class="font-medium flex items-center mb-2">
                                    <i class="fas fa-gamepad text-red-500 mr-2"></i>
                                    No Gaming
                                </h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Computer games, card games, or any form of gaming is strictly prohibited.
                                </p>
                            </div>
                            
                            <a href="#" class="text-sm text-blue-600 dark:text-blue-400 font-medium flex items-center mt-2" id="view-all-rules">
                                <i class="fas fa-external-link-alt mr-1"></i> View all rules
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Rules Modal -->
    <div id="rules-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center sticky top-0 bg-white dark:bg-gray-800 z-10">
                <h3 class="text-lg font-bold flex items-center">
                    <i class="fas fa-clipboard-list text-blue-500 mr-2"></i>
                    Laboratory Rules & Guidelines
                </h3>
                <button id="close-rules-modal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-grow" style="max-height: calc(90vh - 140px);">
                <div class="space-y-6">
                    <div class="rule-section">
                        <h4 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">General Rules</h4>
                        <ul class="space-y-3">
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Students must present a valid ID upon entry to the computer laboratory.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Maintain silence and proper decorum inside the laboratory at all times.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Mobile phones must be switched to silent mode while in the laboratory.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">No eating or drinking inside the laboratory premises.</p>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="rule-section">
                        <h4 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Computer Usage</h4>
                        <ul class="space-y-3">
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Computer games, card games, or any form of gaming is strictly prohibited.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Unauthorized installation of software on laboratory computers is not allowed.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Users are prohibited from changing any computer settings or configurations.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Downloading of non-academic materials, large files, or any unauthorized content is prohibited.</p>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="rule-section">
                        <h4 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Equipment Care</h4>
                        <ul class="space-y-3">
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Report any damaged or malfunctioning equipment to laboratory staff immediately.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Do not disconnect or reconnect any computer peripheral devices.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Keep workstations clean and organized before leaving.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Vandalism or intentional damage to laboratory equipment will result in serious disciplinary action.</p>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="rule-section">
                        <h4 class="text-md font-semibold mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">Safety & Security</h4>
                        <ul class="space-y-3">
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Log out from all accounts before leaving your workstation.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Do not share your account credentials with others.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-check-circle text-green-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Be aware of emergency procedures and exit locations.</p>
                            </li>
                            <li class="flex">
                                <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                                <p class="text-sm">Unauthorized access to restricted areas of the laboratory is strictly prohibited.</p>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 text-right sticky bottom-0 bg-white dark:bg-gray-800 z-10">
                <button id="close-rules-btn" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i> Got it
                </button>
            </div>
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
        
        // Apply fade-in animations on load
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach(function(element) {
                element.style.opacity = '1';
            });
        });
        
        // Modal confirmation for point conversion
        function confirmPointConversion() {
            return confirm('Are you sure you want to convert these points to sessions? This action cannot be undone.');
        }
        
        // Rules modal functionality
        const rulesModal = document.getElementById('rules-modal');
        const viewAllRulesBtn = document.getElementById('view-all-rules');
        const closeRulesModalBtn = document.getElementById('close-rules-modal');
        const closeRulesBtn = document.getElementById('close-rules-btn');
        
        viewAllRulesBtn.addEventListener('click', function(e) {
            e.preventDefault();
            rulesModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
        });
        
        function closeRulesModal() {
            rulesModal.classList.add('hidden');
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        closeRulesModalBtn.addEventListener('click', closeRulesModal);
        closeRulesBtn.addEventListener('click', closeRulesModal);
        
        // Close modal when clicking outside
        rulesModal.addEventListener('click', function(e) {
            if (e.target === rulesModal) {
                closeRulesModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !rulesModal.classList.contains('hidden')) {
                closeRulesModal();
            }
        });
        
        // Modal confirmation for point conversion
        // ...existing code...
    </script>
</body>

</html>