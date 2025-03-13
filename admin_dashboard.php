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
        .glass {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header>
        <nav class="bg-white shadow-md sticky top-0 z-50 glass">
            <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
                <div class="relative flex items-center justify-between h-16">
                    <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                        <div class="hidden sm:block sm:ml-6">
                            <div class="flex space-x-4">
                                <a href="admin_dashboard.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                                <a href="admin_students.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Students</a>
                                <a href="sit_in_records.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Sit-in Records</a>
                                <a href="search_student.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Search Student</a>
                            </div>
                        </div>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                        <a href="admin_logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="container mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow glass">
        <section>
            <article class="bg-gray-50 rounded-lg shadow-lg p-6 glass">
                <h2 class="text-2xl font-bold mb-4">Announcements</h2>
                <form method="post" action="admin_dashboard.php" class="space-y-4">
                    <label for="content" class="block text-sm font-medium text-gray-700">New Announcement</label>
                    <textarea id="content" name="content" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    <div class="flex justify-end">
                        <input type="submit" name="create_announcement" class="bg-green-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-green-700" value="Create Announcement">
                    </div>
                </form>
                <h3 class="text-xl font-bold mb-4">Posted Announcements</h3>
                <hr>
                <div class="mt-8 h-96 overflow-y-auto">
                    <?php foreach ($announcements as $announcement): ?>
                        <article class="bg-white p-4 rounded-lg shadow-md mb-4 glass">
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                            <div class="flex justify-end mt-4 space-x-2">
                                <form method="post" action="admin_dashboard.php" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                    <input type="submit" name="delete_announcement" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700" value="Delete">
                                </form>
                                <button class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(addslashes($announcement['content'])); ?>')">Edit</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>
    </main>
    
    <footer class="text-center p-4 bg-gray-200 mt-8">
        <p>&copy; <?php echo date("Y"); ?>All rights reserved.</p>
    </footer>
</body>
</html>