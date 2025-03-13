<?php
include 'config.php';

// Handle search request via GET
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // Sanitize user input
    $search = $conn->real_escape_string($search);

    // Prepare SQL query to prevent SQL injection
    $sql = "SELECT * FROM users WHERE id_number=? OR CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?";
    $stmt = $conn->prepare($sql);

    // Prepare the LIKE clause for searching
    $search_term = "%$search%";
    $stmt->bind_param("ss", $search, $search_term);

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare response array
    $response = ['success' => false];

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Set the response to success and include the student details
        $response['success'] = true;
        $response['student'] = [
            'id_number' => $row['id_number'],
            'name' => $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'],
            'purpose' => 'C programming', // Default purpose (You can adjust this logic later)
            'lab' => '524', // Default lab (You can adjust this logic later)
            'sessions_left' => $row['sessions_left']
        ];
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();

    // Return the response as JSON for the search
    header('Content-Type: application/json');
    echo json_encode($response);
    exit(); // Stop execution to avoid any further HTML output
}

// Handle sit-in activity POST request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['handle_sit_in'])) {
    $id_number = $_POST['id_number'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $sessions_left = $_POST['sessions_left'] - 1;

    // Update sessions_left in users table
    $sql_update_sessions = "UPDATE users SET sessions_left='$sessions_left' WHERE id_number='$id_number'";
    $conn->query($sql_update_sessions);

    // Insert reservation record in reservations table
    $sql_insert_reservation = "INSERT INTO reservations (id_number, name, sit_in_purpose, lab_number, login_time, logout_time, date) 
                                VALUES ('$id_number', '{$_POST['name']}', '$purpose', '$lab', NOW(), '00:00:00', NOW())";
    $conn->query($sql_insert_reservation);

    // Redirect back to the search page with a success flag
    header("Location: search_student.php?success=true");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student</title>
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

<!-- Search Form -->
<div class="container mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow">
    <h1 class="text-2xl font-bold mb-4 text-center">Search Student</h1>
    <form id="searchForm" class="flex items-center justify-center space-x-4 mb-4">
        <input type="text" id="searchInput" name="search" placeholder="Enter student ID or name" required class="p-2 border border-gray-300 rounded-lg w-64">
        <button type="submit" class="p-2 bg-blue-500 text-white rounded-lg hover:bg-blue-700">Search</button>
    </form>
    <div id="result" class="mt-4 text-left"></div>
</div>

<script>
// Handle Search Form Submission
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const searchInput = document.getElementById('searchInput').value;

    // Send a GET request to search for the student
    fetch(`?search=${encodeURIComponent(searchInput)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show the search result and sit-in form
                const student = data.student;
                const resultContainer = document.getElementById('result');
                resultContainer.innerHTML = `
                    <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded-lg shadow-md">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-3">Student Details</h2>
                        <div class="space-y-2">
                            <p><strong class="font-medium text-gray-700">ID Number:</strong> <span class="text-gray-900">${student.id_number}</span></p>
                            <p><strong class="font-medium text-gray-700">Name:</strong> <span class="text-gray-900">${student.name}</span></p>
                            <p><strong class="font-medium text-gray-700">Sessions Left:</strong> <span class="text-gray-900">${student.sessions_left}</span></p>
                        </div>
                        <h2 class="text-xl font-bold mt-4">Log Sit-in Activity</h2>
                        <form method="post" action="search_student.php" class="space-y-4 mt-4" onsubmit="return logSitInActivity()">
                            <input type="hidden" name="id_number" value="${student.id_number}">
                            <input type="hidden" name="name" value="${student.name}">
                            <input type="hidden" name="sessions_left" value="${student.sessions_left}">
                            <div>
                                <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                                <select name="purpose" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="C programming">C programming</option>
                                    <option value="Java programming">Java programming</option>
                                    <option value="C# programming">C# programming</option>
                                    <option value="PHP programming">PHP programming</option>
                                    <option value="ASP.NET programming">ASP.NET programming</option>
                                </select>
                            </div>
                            <div>
                                <label for="lab" class="block text-sm font-medium text-gray-700">Lab</label>
                                <select name="lab" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="524">524</option>
                                    <option value="526">526</option>
                                    <option value="528">528</option>
                                    <option value="530">530</option>
                                    <option value="Mac Laboratory">Mac Laboratory</option>
                                </select>
                            </div>
                            <div class="flex justify-end">
                                <input type="submit" name="handle_sit_in" class="bg-green-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-green-700" value="Log Sit-in">
                            </div>
                        </form>
                    </div>
                `;
            } else {
                resultContainer.innerHTML = "<p>No student found.</p>";
            }
        })
        .catch(error => console.error('Error:', error));
});

function logSitInActivity() {
    alert('Sit-in activity logged successfully!');
    return true;
}
</script>

</body>
</html>
