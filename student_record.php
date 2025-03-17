<?php
include 'config.php';

// Fetch student records
$sql_students = "SELECT * FROM users";
$result_students = $conn->query($sql_students);
$students = [];
if ($result_students->num_rows > 0) {
    while ($row = $result_students->fetch_assoc()) {
        $students[] = $row;
    }
} else {
    echo "No student records found.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
                                <a href="student_record.php" class="text-gray-900 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium">Students</a>
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
        <h1 class="text-4xl font-bold text-center mb-8">Student Records</h1>
        <div class="overflow-x-auto">
            <table class="w-full text-gray-800 text-center border border-gray-300 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="py-4 px-6">ID Number</th>
                        <th class="py-4 px-6">First Name</th>
                        <th class="py-4 px-6">Middle Name</th>
                        <th class="py-4 px-6">Last Name</th>
                        <th class="py-4 px-6">Email</th>
                        <th class="py-4 px-6">Sessions Left</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $student): ?>
                            <tr class="bg-white">
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['id_number']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['first_name']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['middle_name']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['last_name']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="py-4 px-6"><?php echo htmlspecialchars($student['sessions_left']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="py-4 px-6">No student records found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="text-center p-4 bg-gray-200 mt-8">
        <p>&copy; <?php echo date("Y"); ?> All rights reserved.</p>
    </footer>
</body>
</html>
