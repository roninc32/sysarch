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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 1rem;
            border-radius: 10px;
        }
        .nav-link {
            transition: all 0.3s ease;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: bold;
            color: #ffffff;
        }
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            transform: scale(1.05);
        }
        body {
            background: linear-gradient(135deg, #000000, #434343);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center p-8 text-white">
    
    <header class="fixed top-0 left-0 w-full bg-opacity-90 backdrop-blur-lg glass shadow-lg z-50">
        <nav class="w-full max-w-5xl mx-auto p-4 flex justify-between items-center">
            <ul class="flex space-x-6">
                <li><a href="admin_dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="admin_students.php" class="nav-link">Students</a></li>
                <li><a href="sit_in_records.php" class="nav-link">Sit-in Records</a></li>
                <li><a href="search_student.php" class="nav-link">Search Student</a></li>
            </ul>
            <a href="admin_logout.php" class="px-5 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition-all">Logout</a>
        </nav>
    </header>

    <main class="w-full max-w-5xl p-6 glass rounded-xl mt-24">
        <h1 class="text-3xl font-bold text-center mb-6">Sit-in Records</h1>
        <div class="overflow-x-auto">
            <table class="w-full text-white text-center border border-white border-opacity-30 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-white bg-opacity-10">
                        <th class="py-3 px-5">ID Number</th>
                        <th class="py-3 px-5">Name</th>
                        <th class="py-3 px-5">Purpose</th>
                        <th class="py-3 px-5">Lab</th>
                        <th class="py-3 px-5">Login Time</th>
                        <th class="py-3 px-5">Date</th>
                        <th class="py-3 px-5">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sit_in_records as $record): ?>
                        <tr class="border-b border-white border-opacity-20 hover:bg-white hover:bg-opacity-10 transition">
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['id_number']); ?></td>
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['name']); ?></td>
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['sit_in_purpose']); ?></td>
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['lab_number']); ?></td>
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['login_time']); ?></td>
                            <td class="py-3 px-5"><?php echo htmlspecialchars($record['date']); ?></td>
                            <td class="py-3 px-5">
                                <form method="post" action="logout_student.php">
                                    <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                                    <input type="submit" value="Logout" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition cursor-pointer">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>