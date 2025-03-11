<?php
session_start();

if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

include 'config.php';

// Fetch students
$sql_students = "SELECT * FROM users";
$result_students = $conn->query($sql_students);
$students = [];
if ($result_students->num_rows > 0) {
    while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
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

// Fetch sit-in records
$sql_sit_in = "SELECT * FROM reservations";
$result_sit_in = $conn->query($sql_sit_in);
$sit_in_records = [];
if ($result_sit_in->num_rows > 0) {
    while ($row = $result_sit_in->fetch_assoc()) {
        $sit_in_records[] = $row;
    }
}

// Handle form submission for creating announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_announcement'])) {
    $content = $_POST['content'];
    $sql_create = "INSERT INTO announcements (content) VALUES ('$content')";
    $conn->query($sql_create);
    header("Location: admin_dashboard.php");
    exit();
}

// Handle form submission for editing announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_announcement'])) {
    $id = $_POST['id'];
    $content = $_POST['content'];
    $sql_edit = "UPDATE announcements SET content='$content' WHERE id='$id'";
    $conn->query($sql_edit);
    header("Location: admin_dashboard.php");
    exit();
}

// Handle form submission for deleting announcements
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_announcement'])) {
    $id = $_POST['id'];
    $sql_delete = "DELETE FROM announcements WHERE id='$id'";
    $conn->query($sql_delete);
    header("Location: admin_dashboard.php");
    exit();
}

// Handle sit-in activity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $id_number = $_POST['id_number'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $sessions_left = $_POST['sessions_left'] - 1;

    $sql_update_sessions = "UPDATE users SET sessions_left='$sessions_left' WHERE id_number='$id_number'";
    $conn->query($sql_update_sessions);

    $sql_insert_reservation = "INSERT INTO reservations (id_number, sit_in_purpose, lab_number, login_time, date) VALUES ('$id_number', '$purpose', '$lab', NOW(), NOW())";
    $conn->query($sql_insert_reservation);

    header("Location: admin_dashboard.php");
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
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<nav class="bg-white shadow-md sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="relative flex items-center justify-between h-16">
            <div class="flex-1 flex items-center justify-center sm:items-stretch sm:justify-start">
                <div class="hidden sm:block sm:ml-6">
                    <div class="flex space-x-4">
                        <a href="admin_dashboard.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Dashboard</a>
                        <a href="admin_students.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Students</a>
                        <a href="admin_announcements.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Announcements</a>
                        <a href="admin_sit_in_records.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Sit-in Records</a>
                    </div>
                </div>
            </div>
            <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                <a href="admin_logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Search Student -->
        <div class="col-span-1">
            <div class="bg-gray-50 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Search Student</h2>
                <form id="searchForm" class="space-y-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Search by ID or Name</label>
                        <input type="text" id="search" name="search" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="flex justify-end">
                        <button type="button" onclick="searchStudent()" class="bg-indigo-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Announcements -->
        <div class="col-span-2">
            <div class="bg-gray-50 rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Announcements</h2>
                <form method="post" action="admin_dashboard.php" class="space-y-4">
                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700">New Announcement</label>
                        <textarea id="content" name="content" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <input type="submit" name="create_announcement" class="bg-green-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-green-700" value="Create Announcement">
                    </div>
                    <h3 class="text-xl font-bold mb-4">Posted Announcements</h3>
                    <hr>
                </form>
                <div class="mt-8 h-96 overflow-y-auto">
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="bg-white p-4 rounded-lg shadow-md mb-4">
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                            <div class="flex justify-end mt-4 space-x-2">
                                <form method="post" action="admin_dashboard.php" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
                                    <input type="submit" name="delete_announcement" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700" value="Delete">
                                </form>
                                <button class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700" onclick="editAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars(addslashes($announcement['content'])); ?>')">Edit</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg w-full">
        <h2 class="text-2xl font-bold mb-4">Edit Announcement</h2>
        <form method="post" action="admin_dashboard.php" class="space-y-4">
            <input type="hidden" id="edit_id" name="id">
            <div>
                <label for="edit_content" class="block text-sm font-medium text-gray-700">Content</label>
                <textarea id="edit_content" name="content" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
            </div>
            <div class="flex justify-end">
                <input type="submit" name="edit_announcement" class="bg-blue-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-blue-700" value="Save Changes">
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-gray-700 ml-2" onclick="closeEditAnnouncementModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Student Info Modal -->
<div id="studentInfoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg w-full">
        <h2 class="text-2xl font-bold mb-4">Student Information</h2>
        <div id="studentInfoContent" class="space-y-4">
            <!-- Student information will be displayed here -->
        </div>
        <form method="post" action="admin_dashboard.php" class="space-y-4">
            <input type="hidden" id="sit_in_id_number" name="id_number">
            <div>
                <label for="sit_in_purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                <select id="sit_in_purpose" name="purpose" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="C programming">C programming</option>
                    <option value="Java programming">Java programming</option>
                    <option value="C# programming">C# programming</option>
                    <option value="PHP programming">PHP programming</option>
                    <option value="ASP.NET programming">ASP.NET programming</option>
                </select>
            </div>
            <div>
                <label for="sit_in_lab" class="block text-sm font-medium text-gray-700">Lab</label>
                <select id="sit_in_lab" name="lab" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="524">524</option>
                    <option value="526">526</option>
                    <option value="528">528</option>
                    <option value="530">530</option>
                    <option value="Mac Laboratory">Mac Laboratory</option>
                </select>
            </div>
            <input type="hidden" id="sit_in_sessions_left" name="sessions_left">
            <div class="flex justify-end">
                <input type="submit" name="handle_sit_in" class="bg-green-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-green-700" value="Handle Sit-in">
            </div>
        </form>
        <div class="flex justify-end mt-4">
            <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-gray-700" onclick="closeStudentInfoModal()">Close</button>
        </div>
    </div>
</div>

<script>
function editAnnouncement(id, content) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_content').value = content;
    document.getElementById('editAnnouncementModal').classList.remove('hidden');
}

function closeEditAnnouncementModal() {
    document.getElementById('editAnnouncementModal').classList.add('hidden');
}

function searchStudent() {
    const searchValue = document.getElementById('search').value;
    if (searchValue.trim() === '') {
        alert('Please enter a valid ID or Name.');
        return;
    }

    fetch(`search_student.php?search=${searchValue}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const studentInfo = `
                    <p><strong>ID Number:</strong> ${data.student.id_number}</p>
                    <p><strong>Name:</strong> ${data.student.name}</p>
                    <p><strong>Purpose:</strong> ${data.student.purpose}</p>
                    <p><strong>Lab:</strong> ${data.student.lab}</p>
                    <p><strong>Remaining Sessions:</strong> ${data.student.sessions_left}</p>
                `;
                document.getElementById('studentInfoContent').innerHTML = studentInfo;
                document.getElementById('sit_in_id_number').value = data.student.id_number;
                document.getElementById('sit_in_purpose').value = data.student.purpose;
                document.getElementById('sit_in_lab').value = data.student.lab;
                document.getElementById('sit_in_sessions_left').value = data.student.sessions_left;
                document.getElementById('studentInfoModal').classList.remove('hidden');
            } else {
                alert('Student not found.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while searching for the student.');
        });
}

function closeStudentInfoModal() {
    document.getElementById('studentInfoModal').classList.add('hidden');
}
</script>
</body>
</html>
