<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

$id_number = $_SESSION["username"];

// Get student information
$sql = "SELECT * FROM users WHERE id_number='$id_number'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first_name = $row["first_name"];
    $last_name = $row["last_name"];
    $course_level = $row["course_level"];
    $course = $row["course"];
} else {
    echo "No user found.";
    exit();
}

// Initialize filters and search
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch categories/professors
$sql_categories = "SELECT DISTINCT professor FROM resources ORDER BY professor";
$result_categories = $conn->query($sql_categories);
$categories = [];
while ($result_categories && $row = $result_categories->fetch_assoc()) {
    $categories[] = $row['professor'];
}

// Build query with filters and search
$sql_resources = "SELECT * FROM resources WHERE 1=1";

// Add category filter
if (!empty($category_filter)) {
    $sql_resources .= " AND professor = '" . $conn->real_escape_string($category_filter) . "'";
}

// Add search filter
if (!empty($search_query)) {
    $sql_resources .= " AND (name LIKE '%" . $conn->real_escape_string($search_query) . "%' 
                         OR description LIKE '%" . $conn->real_escape_string($search_query) . "%'
                         OR professor LIKE '%" . $conn->real_escape_string($search_query) . "%')";
}

$sql_resources .= " ORDER BY created_at DESC";

// Execute query
$result_resources = $conn->query($sql_resources);
$resources = [];

// Fetch all resources
if ($result_resources && $result_resources->num_rows > 0) {
    while ($row = $result_resources->fetch_assoc()) {
        $resources[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Resources</title>
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
        
        .resource-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
            margin-top: 20px;
        }
        
        .resource-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .resource-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .resource-header {
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .resource-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 24px;
            background-color: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .resource-file-icon {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent-color);
        }
        
        .resource-title-area {
            flex: 1;
            min-width: 0;
        }
        
        .resource-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--text-primary);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .resource-professor {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .resource-content {
            padding: 16px;
        }
        
        .resource-description {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;  
            overflow: hidden;
            height: 63px;
        }
        
        .resource-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .resource-date {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            background-color: var(--accent-color);
            color: white;
            transition: all 0.2s ease;
        }
        
        .download-btn:hover {
            background-color: var(--accent-hover);
        }
        
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .filter-chip {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            background-color: var(--card-bg);
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .filter-chip:hover {
            background-color: var(--bg-secondary);
        }
        
        .filter-chip.active {
            background-color: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 20px;
            text-align: center;
        }
        
        .empty-icon {
            font-size: 64px;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 20px;
        }
        
        .empty-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .empty-description {
            font-size: 14px;
            color: var(--text-secondary);
            max-width: 400px;
            line-height: 1.5;
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
            
            .topbar {
                padding: 0 16px;
            }
            
            .menu-toggle {
                display: block !important;
            }
            
            .resource-grid {
                grid-template-columns: 1fr;
            }
            
            .search-box {
                width: auto;
                flex: 1;
                max-width: 300px;
            }
        }
    </style>
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
                <a href="reservation.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar"></i></div>
                    <span>Make a Reservation</span>
                </a>
                <a href="view_schedule.php" class="nav-item">
                    <div class="nav-icon"><i class="fas fa-calendar-week"></i></div>
                    <span>View Schedules</span>
                </a>
                <a href="view_resources.php" class="nav-item active">
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
    
    <!-- Main content area -->
    <div class="main-content">
        <div class="topbar">
            <div class="flex items-center">
                <button class="menu-toggle mr-4" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Learning Resources</h1>
            </div>
            
            <div class="topbar-actions">
                <form action="view_resources.php" method="GET" class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Search resources" class="search-input" value="<?php echo htmlspecialchars($search_query); ?>">
                    <?php if (!empty($category_filter)): ?>
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                    <?php endif; ?>
                </form>
                
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
            <!-- Filter Bar -->
            <div class="card filter-bar-container mb-6">
                <div class="p-4">
                    <div class="text-sm font-medium mb-2">Filter by Professor:</div>
                    <div class="filter-bar">
                        <a href="view_resources.php<?php echo !empty($search_query) ? '?search=' . urlencode($search_query) : ''; ?>" 
                           class="filter-chip <?php echo empty($category_filter) ? 'active' : ''; ?>">
                            All
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="view_resources.php?category=<?php echo urlencode($category); ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                               class="filter-chip <?php echo $category_filter === $category ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Resources Grid -->
            <?php if (empty($resources)): ?>
                <div class="card">
                    <div class="empty-state">
                        <i class="fas fa-cubes empty-icon"></i>
                        <h2 class="empty-title">No resources found</h2>
                        <p class="empty-description">
                            <?php if (!empty($search_query) || !empty($category_filter)): ?>
                                Try adjusting your search or filter criteria.
                            <?php else: ?>
                                Resources will appear here once they are added by faculty.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="resource-grid">
                    <?php foreach ($resources as $resource): ?>
                        <div class="resource-card">
                            <div class="resource-header">
                                <div class="resource-icon <?php echo !empty($resource['file_path']) ? 'resource-file-icon' : ''; ?>">
                                    <i class="<?php echo !empty($resource['file_path']) ? 'fas fa-file-alt' : 'fas fa-cube'; ?>"></i>
                                </div>
                                <div class="resource-title-area">
                                    <h3 class="resource-title" title="<?php echo htmlspecialchars($resource['name']); ?>">
                                        <?php echo htmlspecialchars($resource['name']); ?>
                                    </h3>
                                    <div class="resource-professor">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($resource['professor']); ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="resource-content">
                                <?php if (!empty($resource['description'])): ?>
                                    <div class="resource-description">
                                        <?php echo nl2br(htmlspecialchars($resource['description'])); ?>
                                    </div>
                                <?php else: ?>
                                    <div class="resource-description text-gray-400 dark:text-gray-500">
                                        No description available for this resource.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="resource-footer">
                                <div class="resource-date">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <?php echo date('F j, Y', strtotime($resource['created_at'])); ?>
                                </div>
                                <?php if (!empty($resource['file_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($resource['file_path']); ?>" class="download-btn" download>
                                        <i class="fas fa-download"></i>
                                        <span>Download</span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">No file attached</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>
