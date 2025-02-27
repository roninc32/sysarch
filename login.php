<?php
include 'config.php';

session_start();
if (isset($_SESSION["logout_message"])) {
    $logout_message = $_SESSION["logout_message"];
    unset($_SESSION["logout_message"]);
} else {
    $logout_message = "";
}

$passwordError = "";
$idError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $id_number = $_POST["username"]; // Change to id_number
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE id_number='$id_number'"; // Change to id_number
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["username"] = $id_number; // Change to id_number
            header("Location: dashboard.php");
            exit();
        } else {
            $passwordError = "Invalid password.";
        }
    } else {
        $idError = "No user found with that ID number.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container max-w-md mx-auto bg-white p-8 rounded-lg shadow-lg">
        <div class="header flex justify-center mb-6">
            <img src="assets/images/ccs-logo.png" alt="CCS Logo" class="w-24 h-24 mx-2">
            <img src="assets/images/uc-main-logo.jpg" alt="UC Logo" class="w-24 h-24 mx-2">
        </div>
        <h1 class="text-2xl font-bold text-center mb-6">Sit-in Monitoring System</h1>
        <?php if ($logout_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $logout_message; ?></span>
            </div>
        <?php endif; ?>
        <form method="post" action="login.php" class="space-y-4">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">ID NUMBER</label>
                <input type="text" id="username" name="username" required value="<?php echo isset($id_number) ? htmlspecialchars($id_number) : ''; ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <span class="text-red-500 text-sm"><?php echo $idError; ?></span>
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">PASSWORD</label>
                <input type="password" id="password" name="password" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <span class="text-red-500 text-sm"><?php echo $passwordError; ?></span>
            </div>
            <div class="button-container flex justify-between items-center mt-6">
                <input type="submit" name="login" value="Login" class="bg-indigo-500 text-white px-4 py-2 rounded-md shadow-sm hover:bg-indigo-700">
                <a href="register.php" class="text-gray-500 hover:underline">Sign Up</a>
            </div>
        </form>
    </div>
</body>
</html>
