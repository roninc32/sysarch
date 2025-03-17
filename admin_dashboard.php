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

// Fetch sit-in records with pagination
$sql_sit_in = "SELECT * FROM reservations LIMIT ?, ?";
$stmt = $conn->prepare($sql_sit_in);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result_sit_in = $stmt->get_result();
$sit_in_records = $result_sit_in->fetch_all(MYSQLI_ASSOC);

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .nav-container {
            @apply bg-white bg-opacity-90 backdrop-filter backdrop-blur-lg border-b border-gray-200;
        }
        
        .nav-link {
            @apply px-4 py-2 text-gray-600 hover:text-indigo-600 font-medium transition-all duration-200
                relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 
                after:bg-indigo-600 after:transition-all after:duration-200 hover:after:w-full;
        }
        
        .nav-link.active {
            @apply text-indigo-600 after:w-full;
        }
        
        .logout-btn {
            @apply px-4 py-2 text-red-600 border border-red-600 rounded-lg 
                hover:bg-red-600 hover:text-white transition-all duration-200
                font-medium focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="fixed w-full top-0">
        <nav class="nav-container">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <img class="h-10 w-auto" src="assets/images/ccs-logo.png" alt="CCS Logo">
                        </div>
                        <div class="hidden md:block ml-10">
                            <div class="flex items-baseline space-x-8">
                                <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                                    Dashboard
                                </a>
                                <a href="student_record.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                                    Students
                                </a>
                                <a href="sit_in_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                    Sit-in Records
                                </a>
                                <a href="search_student.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                    Search Student
                                </a>
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="admin_logout.php" class="logout-btn">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
<main class="container mx-auto p-8 mt-20">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Statistics Cards -->
        <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Total Students</h3>
            <p class="text-3xl font-bold text-indigo-600"><?php echo count($students); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Active Sessions</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo count($sit_in_records); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Total Announcements</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo count($announcements); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Announcements Section -->
        <section class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Announcements</h2>
                <button class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600 transition-all duration-300" onclick="showAnnouncementForm()">New Announcement</button>
            </div>

            <!-- Announcement Form -->
            <form method="post" action="admin_dashboard.php" class="hidden space-y-4 mb-6" id="announcementForm">
                <div class="bg-gray-50 rounded-lg p-4">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">New Announcement</label>
                    <textarea id="content" name="content" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                    <div class="flex justify-end mt-4">
                        <input type="submit" name="create_announcement" class="bg-green-500 text-white px-6 py-2 rounded-lg hover:bg-green-600 transition-all duration-300" value="Post">
                    </div>
                </div>
            </form>

            <!-- Announcements List -->
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <?php foreach ($announcements as $announcement): ?>
                    <article class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition-all duration-300">
                        <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500"><?php echo date('F j, Y', strtotime($announcement['created_at'])); ?></span>
                            <div class="space-x-2">
                                <button class="text-blue-500 hover:text-blue-700" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(addslashes($announcement['content'])); ?>')">Edit</button>
                                <form method="post" action="admin_dashboard.php" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" name="delete_announcement" class="text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Recent Activities Section -->
        <section class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Recent Activities</h2>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <?php foreach ($sit_in_records as $record): ?>
                    <div class="bg-gray-50 rounded-lg p-4 hover:shadow-md transition-all duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($record['name']); ?></h4>
                                <p class="text-sm text-gray-600">Lab: <?php echo htmlspecialchars($record['lab_number']); ?></p>
                                <p class="text-sm text-gray-600">Purpose: <?php echo htmlspecialchars($record['sit_in_purpose']); ?></p>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($record['login_time'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</main>

    <script>
    function showAnnouncementForm() {
        const form = document.getElementById('announcementForm');
        form.classList.toggle('hidden');
    }

    function editAnnouncement(id, content) {
        const newContent = prompt('Edit announcement:', content);
        if (newContent && newContent !== content) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'admin_dashboard.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            const contentInput = document.createElement('input');
            contentInput.type = 'hidden';
            contentInput.name = 'content';
            contentInput.value = newContent;
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'edit_announcement';
            submitInput.value = '1';
            
            form.appendChild(idInput);
            form.appendChild(contentInput);
            form.appendChild(submitInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
    <footer class="text-center p-4 bg-gray-200 mt-8">
        <p>&copy; <?php echo date("Y"); ?>All rights reserved.</p>
    </footer>
</body>
</html>