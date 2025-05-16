<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

// Create resources table if it doesn't exist
$create_resources_table = "CREATE TABLE IF NOT EXISTS resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    professor VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($create_resources_table) !== TRUE) {
    // Handle table creation error
    $_SESSION['message'] = "Error creating resources table: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

// Handle resource addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_resource'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $professor = $conn->real_escape_string($_POST['professor']);
    $description = $conn->real_escape_string($_POST['description']);
    $file_path = '';
    
    // Handle file upload if present
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === 0) {
        $upload_dir = 'uploads/resources/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['resource_file']['name'];
        $file_path = $upload_dir . $file_name;
        
        // Move uploaded file to destination
        if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $file_path)) {
            // File uploaded successfully
        } else {
            $_SESSION['message'] = "Error uploading file";
            $_SESSION['message_type'] = "danger";
        }
    }
    
    // Insert resource into database
    $sql = "INSERT INTO resources (name, professor, description, file_path) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $professor, $description, $file_path);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Resource added successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding resource: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
    header("Location: resources.php");
    exit();
}

// Handle resource editing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_resource'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    $professor = $conn->real_escape_string($_POST['professor']);
    $description = $conn->real_escape_string($_POST['description']);
    
    // Get current file path
    $file_path_query = "SELECT file_path FROM resources WHERE id = ?";
    $stmt = $conn->prepare($file_path_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file_path = $result->fetch_assoc()['file_path'];
    
    // Handle file upload if present
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === 0) {
        // Delete old file if exists
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path);
        }
        
        $upload_dir = 'uploads/resources/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = time() . '_' . $_FILES['resource_file']['name'];
        $file_path = $upload_dir . $file_name;
        
        // Move uploaded file to destination
        if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $file_path)) {
            // File uploaded successfully
        } else {
            $_SESSION['message'] = "Error uploading file";
            $_SESSION['message_type'] = "danger";
            $file_path = $result->fetch_assoc()['file_path']; // Keep old file path
        }
    }
    
    // Update resource in database
    $sql = "UPDATE resources SET name = ?, professor = ?, description = ?, file_path = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $name, $professor, $description, $file_path, $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Resource updated successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating resource: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
    header("Location: resources.php");
    exit();
}

// Handle resource deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_resource'])) {
    $id = $conn->real_escape_string($_POST['id']);
    
    // Get file path first to delete the file
    $file_path_query = "SELECT file_path FROM resources WHERE id = ?";
    $stmt = $conn->prepare($file_path_query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file_path = $result->fetch_assoc()['file_path'];
    
    // Delete file if exists
    if (!empty($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete from database
    $sql = "DELETE FROM resources WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Resource deleted successfully";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting resource: " . $conn->error;
        $_SESSION['message_type'] = "danger";
    }
    
    $stmt->close();
    header("Location: resources.php");
    exit();
}

// Fetch resources with pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM resources ORDER BY created_at DESC LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
$resources = [];

while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM resources";
$count_result = $conn->query($count_sql);
$total_resources = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_resources / $limit);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Admin Dashboard</title>
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
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
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
            
            .menu-toggle {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar with categories -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <i class="fas fa-laptop-code"></i>
                </div>
                <div class="logo-text">SIT-IN Admin</div>
            </div>
        </div>
        
        <div class="sidebar-content">
            <div class="sidebar-section">
                <a href="admin_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-tachometer-alt"></i></div>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Management</div>
                <a href="student_record.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
                    <span>Students</span>
                </a>
                <a href="admin_reservation.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'admin_reservation.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                    <span>Reservations</span>
                </a>
                <a href="sit_in_records.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
                    <span>Sit-in Records</span>
                </a>
                <a href="search_student.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-search"></i></div>
                    <span>Search</span>
                </a>
            </div>
            
            <div class="sidebar-section">
                <div class="section-title">Features</div>
                <a href="schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>Schedules</span>
                </a>
                <a href="feedback.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-comments"></i></div>
                    <span>Feedback</span>
                </a>
                <a href="resources.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'resources.php' ? 'active' : ''; ?>">
                    <div class="nav-icon"><i class="fas fa-cube"></i></div>
                    <span>Resources</span>
                </a>
            </div>
        </div>
        
        <div class="user-section">
            <div class="user-info">
                <div class="user-avatar">A</div>
                <div class="user-details">
                    <div class="user-name">Admin User</div>
                    <div class="user-role">System Administrator</div>
                </div>
                <div>
                    <a href="admin_logout.php" title="Logout">
                        <i class="fas fa-sign-out-alt text-gray-400 hover:text-red-500"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overlay" id="overlay"></div>
    
    <!-- Main content area -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Resource Hub</h1>
            </div>
            
            <div class="topbar-actions">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" placeholder="Search resources" class="search-input" id="resourceSearch">
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
            <!-- Status Message -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="mb-4 p-4 rounded-lg <?php echo $_SESSION['message_type'] == 'success' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'; ?>">
                    <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <!-- Resource Management -->
            <div class="card mb-6">
                <div class="card-header">
                    <h2 class="card-title flex items-center">
                        <i class="fas fa-cube mr-2 text-blue-500"></i> Resource Management
                    </h2>
                    <button class="btn btn-primary" onclick="showResourceForm()">
                        <i class="fas fa-plus"></i> Add Resource
                    </button>
                </div>
                
                <!-- Add Resource Form -->
                <div id="resourceForm" class="hidden p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold mb-4">Add New Resource</h3>
                    <form action="resources.php" method="POST" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resource Name</label>
                                <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                            
                            <div>
                                <label for="professor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Professor</label>
                                <input type="text" id="professor" name="professor" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea id="description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <div class="mt-4">
                            <label for="resource_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload File (Optional)</label>
                            <input type="file" id="resource_file" name="resource_file" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="button" class="btn btn-outline mr-2" onclick="hideResourceForm()">Cancel</button>
                            <button type="submit" name="add_resource" class="btn btn-primary">Add Resource</button>
                        </div>
                    </form>
                </div>
                
                <!-- Resources Table -->
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700" id="resourceTable">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">RESOURCE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">PROFESSOR</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">DESCRIPTION</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ADDED</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <?php if (empty($resources)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="inline-flex flex-col items-center">
                                                <i class="fas fa-cubes text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                                                <p class="text-lg font-medium text-gray-500 dark:text-gray-400">No resources available</p>
                                                <p class="text-sm text-gray-400 dark:text-gray-500 mt-2">Click "Add Resource" to create your first resource.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($resources as $resource): ?>
                                        <tr class="resource-row hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if (!empty($resource['file_path'])): ?>
                                                        <div class="flex-shrink-0 h-8 w-8 rounded-md bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-blue-500 dark:text-blue-400">
                                                            <i class="fas fa-file-alt"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex-shrink-0 h-8 w-8 rounded-md bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400">
                                                            <i class="fas fa-cube"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="ml-3">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo htmlspecialchars($resource['name']); ?></div>
                                                        <?php if (!empty($resource['file_path'])): ?>
                                                            <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" target="_blank" class="text-xs text-blue-500 hover:underline">Download file</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300"><?php echo htmlspecialchars($resource['professor']); ?></td>
                                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                                <?php echo !empty($resource['description']) ? htmlspecialchars($resource['description']) : '<span class="text-gray-400 dark:text-gray-500">No description</span>'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                <?php echo date('M d, Y', strtotime($resource['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <div class="flex space-x-2">
                                                    <button class="btn btn-sm btn-outline" onclick="editResource(<?php echo $resource['id']; ?>, '<?php echo addslashes(htmlspecialchars($resource['name'])); ?>', '<?php echo addslashes(htmlspecialchars($resource['professor'])); ?>', '<?php echo addslashes(htmlspecialchars($resource['description'])); ?>')">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?php echo $resource['id']; ?>, '<?php echo addslashes(htmlspecialchars($resource['name'])); ?>')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-center mt-6">
                            <nav class="flex items-center space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="px-3 py-1 rounded-md <?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Resource Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold">Edit Resource</h3>
                </div>
                <div class="p-6">
                    <form id="editForm" action="resources.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-4">
                            <label for="edit_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Resource Name</label>
                            <input type="text" id="edit_name" name="name" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_professor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Professor</label>
                            <input type="text" id="edit_professor" name="professor" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                            <textarea id="edit_description" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_resource_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload New File (Optional)</label>
                            <input type="file" id="edit_resource_file" name="resource_file" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Leave empty to keep current file</p>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="button" class="btn btn-outline mr-2" onclick="closeEditModal()">Cancel</button>
                            <button type="submit" name="edit_resource" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                <div class="p-6 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Delete Resource</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Are you sure you want to delete "<span id="delete_resource_name"></span>"? This action cannot be undone.</p>
                    
                    <div class="flex justify-center space-x-3">
                        <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                        <form id="deleteForm" action="resources.php" method="POST">
                            <input type="hidden" name="id" id="delete_id">
                            <button type="submit" name="delete_resource" class="btn btn-danger">Delete</button>
                        </form>
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
        
        // Resource form toggle
        function showResourceForm() {
            document.getElementById('resourceForm').classList.remove('hidden');
        }
        
        function hideResourceForm() {
            document.getElementById('resourceForm').classList.add('hidden');
        }
        
        // Edit resource
        function editResource(id, name, professor, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_professor').value = professor;
            document.getElementById('edit_description').value = description;
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Delete confirmation
        function confirmDelete(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_resource_name').textContent = name;
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        
        // Search functionality
        document.getElementById('resourceSearch').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#resourceTable tbody tr.resource-row');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
        
        // Close modals when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>
