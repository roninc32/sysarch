<?php
include 'config.php';

// Sanitize user input
$search = $conn->real_escape_string($_GET['search']);

// Prepare the SQL query with placeholders to prevent SQL injection
$sql = "SELECT * FROM users WHERE id_number=? OR CONCAT_WS(' ', first_name, middle_name, last_name) LIKE ?";
$stmt = $conn->prepare($sql);

// Use the wildcard character for the LIKE clause and bind parameters
$search_term = "%$search%";
$stmt->bind_param("ss", $search, $search_term);

// Execute the query
$stmt->execute();
$result = $stmt->get_result();

$response = ['success' => false];

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    // Check for valid sit_in_purpose
    $valid_purposes = ['C programming', 'Java programming', 'C# programming', 'PHP programming', 'ASP.NET programming'];
    $purpose = isset($row['sit_in_purpose']) && in_array($row['sit_in_purpose'], $valid_purposes)
        ? $row['sit_in_purpose']
        : 'C programming';

    // Check for valid lab_number
    $valid_labs = ['524', '526', '528', '530', 'Mac Laboratory'];
    $lab = isset($row['lab_number']) && in_array($row['lab_number'], $valid_labs)
        ? $row['lab_number']
        : '524';

    // Set the response to success and include the student details
    $response['success'] = true;
    $response['student'] = [
        'id_number' => $row['id_number'],
        'name' => $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'],
        'purpose' => $purpose,
        'lab' => $lab,
        'sessions_left' => $row['sessions_left']
    ];
}

$stmt->close();
$conn->close();

// Output the response in JSON format
echo json_encode($response);
?>
