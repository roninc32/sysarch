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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check for admin credentials
    if ($username == 'admin' && $password == 'admin') {
        // Admin login successful
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    }

    $id_number = $_POST["username"]; // Change to id_number
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE id_number='$id_number'"; // Change to id_number
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["username"] = $id_number; // Change to id_number
            $_SESSION["is_admin"] = isset($row["is_admin"]) ? $row["is_admin"] : 0;
            
            if ($_SESSION["is_admin"] == 1) {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
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
    <title>Login | UC Sit-in Monitoring</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('assets/images/pattern.png');
            background-size: 200px;
            opacity: 0.1;
            pointer-events: none;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            background-color: white;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
        }
        
        .login-header {
            padding: 2rem;
            text-align: center;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            position: relative;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 10px;
            background-color: white;
            clip-path: ellipse(50% 100% at 50% 100%);
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .input-group label {
            position: absolute;
            top: 50%;
            left: 3rem; /* Move label after icon */
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .input-group input {
            width: 100%;
            padding: 1rem;
            padding-left: 3rem; /* More space for the icon */
            background-color: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            background-color: white;
        }
        
        .input-group input:focus + label,
        .input-group input:not(:placeholder-shown) + label {
            top: 0;
            left: 0.75rem;
            padding: 0 0.25rem;
            font-size: 0.75rem;
            background-color: white;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
        }
        
        .primary-button {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .primary-button:hover {
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }
        
        .primary-button:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
        }
        
        .primary-button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }
        
        .primary-button .button-icon {
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        .primary-button:hover .button-icon {
            transform: translateX(3px);
        }
        
        .register-link {
            margin-top: 1.5rem;
            text-align: center;
            color: #4b5563;
            font-size: 0.875rem;
        }
        
        .register-link a {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .register-link a:hover {
            color: #6366f1;
            text-decoration: underline;
        }
        
        .alert {
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 0.5rem;
            position: relative;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert-success {
            background-color: #ecfdf5;
            border-left: 4px solid #10b981;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #b91c1c;
        }
        
        .alert-icon {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .alert-icon i {
            margin-right: 0.5rem;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
            margin-right: 0.5rem;
            border-radius: 0.25rem;
            border: 2px solid #e5e7eb;
            background-color: white;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .remember-me input[type="checkbox"]:checked {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
        
        @media (max-width: 640px) {
            .login-card {
                max-width: 100%;
                border-radius: 1rem;
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="assets/images/uc-main-logo.jpg" alt="UC Logo" class="w-16 h-16 rounded-full mx-auto mb-4 border-2 border-white/20 shadow-lg">
                <h1 class="text-xl font-bold">UC Sit-In Monitoring System</h1>
                <p class="text-indigo-100 mt-2 text-sm">College of Computer Studies</p>
            </div>
            
            <div class="login-body">
                <?php if ($logout_message): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                        <span class="font-medium">Success</span>
                    </div>
                    <p><?php echo $logout_message; ?></p>
                </div>
                <?php endif; ?>
                
                <?php if ($idError || $passwordError): ?>
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="font-medium">Error</span>
                    </div>
                    <p><?php echo $idError ? $idError : $passwordError; ?></p>
                </div>
                <?php endif; ?>
                
                <form method="post" action="login.php" id="loginForm">
                    <div class="input-group">
                        <i class="fas fa-id-card input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        <label for="username">ID Number</label>
                    </div>
                    
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="" required>
                        <label for="password">Password</label>
                        <span class="toggle-password" onclick="togglePasswordVisibility()">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    
                    <div class="mb-4">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="primary-button" id="loginButton">
                        <span class="flex items-center justify-center">
                            <i class="fas fa-sign-in-alt button-icon"></i>
                            Sign In
                        </span>
                    </button>
                </form>
                
                <div class="register-link mt-6">
                    <p>Don't have an account? <a href="register.php">Create one</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordField = document.getElementById('password');
            const icon = document.querySelector('.toggle-password i');
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
        
        // Add loading state to button when form is submitted
        document.getElementById('loginForm').addEventListener('submit', function() {
            const button = document.getElementById('loginButton');
            button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Signing in...';
            button.disabled = true;
        });
        
        // Automatically focus the ID field when page loads
        window.onload = function() {
            document.getElementById('username').focus();
        };
        
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
