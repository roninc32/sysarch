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
    $student_name = $row["first_name"] . ' ' . $row["middle_name"] . ' ' . $row["last_name"];
    
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
    <title>Reservation</title>
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
        
        /* Form input styles */
        input, select, textarea {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: all 0.2s;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
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
        
        /* Simple fade in animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-out forwards;
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
        
        /* Tab styling */
        .tab-container {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--card-border);
        }
        
        .tab-button {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            margin-right: 0.5rem;
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
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Status badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        
        .status-pending {
            background-color: var(--bg-secondary);
            color: var(--yellow);
        }
        
        .status-approved {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--green);
        }
        
        .status-disapproved {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--red);
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
                    <a href="history.php" class="nav-link">
                        <i class="fas fa-history mr-2"></i> History
                    </a>
                    <a href="reservation.php" class="nav-link active">
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
            <a href="history.php" class="nav-link block mb-1">
                <i class="fas fa-history mr-2"></i> History
            </a>
            <a href="reservation.php" class="nav-link block mb-1 active">
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
            <h1 class="text-2xl font-bold">Reservations</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Manage your sit-in sessions</p>
        </div>
        
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

        <!-- Tab Navigation -->
        <div class="tab-container">
            <button class="tab-button active" id="tab-new" onclick="switchTab('new')">
                <i class="fas fa-calendar-plus mr-2"></i>New Reservation
            </button>
            <button class="tab-button" id="tab-history" onclick="switchTab('history')">
                <i class="fas fa-history mr-2"></i>Reservation History
            </button>
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
                    <div class="p-6">
                        <div class="section-header mb-4">
                            <i class="fas fa-calendar-plus icon"></i>
                            <h2>Reservation Details</h2>
                        </div>
                        
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-4">
                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Purpose:</label>
                                <select id="purpose" name="purpose" required class="w-full">
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
                                    <label for="lab_number" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Laboratory:</label>
                                    <select id="lab_number" name="lab_number" required onchange="updatePCList()">
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
                                    <label for="pc_number" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">PC Number:</label>
                                    <select id="pc_number" name="pc_number" required>
                                        <option value="">Select PC Number</option>
                                        <?php
                                        // PCs will be populated via JavaScript based on lab selection
                                        ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="date" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Date:</label>
                                    <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div>
                                    <label for="time_in" class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Time In:</label>
                                    <input type="time" id="time_in" name="time_in" required>
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
                <div class="p-6">
                    <div class="section-header mb-4">
                        <i class="fas fa-history icon"></i>
                        <h2>Your Reservation History</h2>
                    </div>
                    
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Laboratory</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PC #</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <?php while ($row = $history_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php echo date('h:i A', strtotime($row['time_in'])); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php echo htmlspecialchars($row['laboratory']); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php echo htmlspecialchars($row['pc_number']); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <?php echo htmlspecialchars($row['purpose']); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <span class="status-badge <?php echo 'status-' . $row['status']; ?>">
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <?php echo htmlspecialchars($row['admin_comment'] ?? ''); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-calendar-xmark text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500 dark:text-gray-400">You don't have any reservation history yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
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
    </script>
</body>
</html>
