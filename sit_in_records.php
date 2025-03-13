<?php
include 'config.php';

// Fetch sit-in records
$sql_sit_in = "SELECT * FROM reservations";
$result_sit_in = $conn->query($sql_sit_in);
$sit_in_records = [];
if ($result_sit_in->num_rows > 0) {
    while ($row = $result_sit_in->fetch_assoc()) {
        $sit_in_records[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in Records</title>
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

<div class="container mx-auto bg-white p-8 rounded-lg shadow-lg mt-8 flex-grow">
    <h1 class="text-2xl font-bold mb-4 text-center">Sit-in Records</h1>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b border-gray-200">ID Number</th>
                    <th class="py-2 px-4 border-b border-gray-200">Name</th>
                    <th class="py-2 px-4 border-b border-gray-200">Purpose</th>
                    <th class="py-2 px-4 border-b border-gray-200">Lab</th>
                    <th class="py-2 px-4 border-b border-gray-200">Login Time</th>
                    <th class="py-2 px-4 border-b border-gray-200">Date</th>
                    <th class="py-2 px-4 border-b border-gray-200">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sit_in_records as $record): ?>
                    <tr>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['id_number']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['name']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['login_time']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200"><?php echo htmlspecialchars($record['date']); ?></td>
                        <td class="py-2 px-4 border-b border-gray-200">
                            <form method="post" action="logout_student.php">
                                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                <input type="submit" value="Logout" class="bg-red-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-red-700">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
