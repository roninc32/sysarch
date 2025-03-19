<?php
include 'config.php';

// Handle search request via GET
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    // Sanitize user input
    $search = $conn->real_escape_string($search);

    // First check if student exists
    $sql = "SELECT u.*, CASE WHEN a.student_id IS NOT NULL THEN 1 ELSE 0 END as has_active_sitin 
            FROM users u 
            LEFT JOIN active_sit_ins a ON u.id_number = a.student_id 
            WHERE u.id_number=? OR CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name) LIKE ?";
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
            'sessions_left' => $row['sessions_left'],
            'has_active_sitin' => $row['has_active_sitin']
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
    $name = $_POST['name'];
    $purpose = $_POST['purpose'];
    $lab = $_POST['lab'];
    $sessions_left = $_POST['sessions_left'] - 1;
    $login_time = date('H:i:s');
    $current_date = date('Y-m-d');

    // Check for active sit-in first
    $check_sql = "SELECT COUNT(*) as count FROM active_sit_ins WHERE student_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $id_number);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $active_count = $check_result->fetch_assoc()['count'];
    
    if ($active_count > 0) {
        echo "<script>alert('This student already has an active sit-in session!'); window.location.href='search_student.php';</script>";
        exit();
    }

    // Update sessions_left in users table
    $sql_update_sessions = "UPDATE users SET sessions_left = ? WHERE id_number = ?";
    $stmt_update = $conn->prepare($sql_update_sessions);
    $stmt_update->bind_param("is", $sessions_left, $id_number);
    $stmt_update->execute();

    // Insert into active_sit_ins table
    $sql_insert = "INSERT INTO active_sit_ins (student_id, name, sit_in_purpose, lab_number, login_time, date) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("ssssss", $id_number, $name, $purpose, $lab, $login_time, $current_date);
    
    if ($stmt_insert->execute()) {
        header("Location: sit_in_records.php");
        exit();
    } else {
        echo "Error: " . $conn->error;
    }

    $stmt_update->close();
    $stmt_insert->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <style>
        .nav-container {
            @apply bg-gradient-to-r from-indigo-600 to-blue-500 shadow-lg;
        }
        
        .nav-link {
            @apply px-4 py-2 text-white hover:text-white/90 font-medium transition-all duration-200
                relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 
                after:bg-white after:transition-all after:duration-200 hover:after:w-full;
        }
        
        .nav-link.active {
            @apply text-white after:w-full font-bold;
        }
        
        .logout-btn {
            @apply px-4 py-2 text-white border-2 border-white/80 rounded-lg 
                hover:bg-white hover:text-indigo-600 transition-all duration-200
                font-medium focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2
                focus:ring-offset-indigo-600;
        }

        .nav-brand {
            @apply flex items-center space-x-3 text-white;
        }

        .nav-brand-text {
            @apply text-lg font-bold hidden md:block;
        }

        .glass-morphism {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            backdrop-filter: blur(4px);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col" style="background-image: url('assets/images/bg.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">
    <nav class="nav-container">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="nav-brand">
                        <img class="h-10 w-auto" src="assets/images/ccs-logo.png" alt="CCS Logo">
                    </div>
                    <div class="hidden md:block ml-10">
                        <div class="flex items-baseline space-x-4">
                            <a href="admin_dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                                <i class="fas fa-home mr-2"></i>Dashboard
                            </a>
                            <a href="student_record.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_record.php' ? 'active' : ''; ?>">
                                <i class="fas fa-users mr-2"></i>Students
                            </a>
                            <a href="sit_in_records.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sit_in_records.php' ? 'active' : ''; ?>">
                                <i class="fas fa-clipboard-list mr-2"></i>Sit-in Records
                            </a>
                            <a href="search_student.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'search_student.php' ? 'active' : ''; ?>">
                                <i class="fas fa-search mr-2"></i>Search Student
                            </a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <a href="admin_logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>
<main class="container mx-auto p-8 mt-20">
    <div class="glass-morphism rounded-lg shadow-lg p-6 mb-6">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Search Student</h1>
            <p class="text-gray-600 mt-2">Enter student ID or name to search</p>
        </div>

        <form id="searchForm" class="max-w-md mx-auto mb-8">
            <div class="flex gap-4">
                <input type="text" 
                       id="searchInput" 
                       name="search" 
                       placeholder="Enter student ID or name" 
                       required 
                       class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                <button type="submit" 
                        class="bg-indigo-500 text-white px-6 py-3 rounded-lg hover:bg-indigo-600 transition duration-200 ease-in-out transform hover:scale-105">
                    Search
                </button>
            </div>
        </form>

        <div id="result" class="max-w-3xl mx-auto"></div>
    </div>
</main>

<footer class="text-center p-4 bg-gray-200 mt-auto">
    <p>&copy; <?php echo date("Y"); ?> All rights reserved.</p>
</footer>

<script>
document.getElementById('searchForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const searchInput = document.getElementById('searchInput').value;

    fetch(`?search=${encodeURIComponent(searchInput)}`)
        .then(response => response.json())
        .then(data => {
            const resultContainer = document.getElementById('result');
            if (data.success) {
                const student = data.student;
                if (student.has_active_sitin) {
                    resultContainer.innerHTML = `
                        <div class="glass-morphism p-6 rounded-lg shadow-lg">
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                                This student already has an active sit-in session!
                            </div>
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b">Student Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <p class="text-gray-600">ID Number</p>
                                    <p class="font-semibold text-gray-800">${student.id_number}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Name</p>
                                    <p class="font-semibold text-gray-800">${student.name}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Sessions Left</p>
                                    <p class="font-semibold text-gray-800">${student.sessions_left}</p>
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    resultContainer.innerHTML = `
                        <div class="glass-morphism p-6 rounded-lg shadow-lg">
                            <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b">Student Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <p class="text-gray-600">ID Number</p>
                                    <p class="font-semibold text-gray-800">${student.id_number}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600">Name</p>
                                    <p class="font-semibold text-gray-800">${student.name}</p>
                                </div>z
                                <div>
                                    <p class="text-gray-600">Sessions Left</p>
                                    <p class="font-semibold text-gray-800">${student.sessions_left}</p>
                                </div>
                            </div>

                            <h3 class="text-xl font-bold text-gray-800 mb-4">Log Sit-in Activity</h3>
                            <form method="post" action="search_student.php" class="space-y-4" onsubmit="return logSitInActivity()">
                                <input type="hidden" name="id_number" value="${student.id_number}">
                                <input type="hidden" name="name" value="${student.name}">
                                <input type="hidden" name="sessions_left" value="${student.sessions_left}">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-600 mb-2">Purpose</label>
                                        <select name="purpose" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="C programming">C programming</option>
                                            <option value="Java programming">Java programming</option>
                                            <option value="C# programming">C# programming</option>
                                            <option value="PHP programming">PHP programming</option>
                                            <option value="ASP.NET programming">ASP.NET programming</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-600 mb-2">Lab</label>
                                        <select name="lab" required class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="524">524</option>
                                            <option value="526">526</option>
                                            <option value="528">528</option>
                                            <option value="530">530</option>
                                            <option value="Mac Laboratory">Mac Laboratory</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end mt-6">
                                    <button type="submit" name="handle_sit_in" class="bg-indigo-500 text-white px-6 py-3 rounded-lg hover:bg-indigo-600 transition duration-200 ease-in-out transform hover:scale-105">
                                        Log Sit-in
                                    </button>
                                </div>
                            </form>
                        </div>
                    `;
                }
            } else {
                resultContainer.innerHTML = `
                    <div class="glass-morphism p-6 rounded-lg shadow-lg text-center">
                        <p class="text-red-500 font-semibold">No student found.</p>
                    </div>
                `;
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
