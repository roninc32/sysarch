<?php
session_start();
include 'config.php';

$passwordError = "";
$accountError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST["id_number"];
    $last_name = $_POST["last_name"];
    $first_name = $_POST["first_name"];
    $middle_name = $_POST["middle_name"];
    $course_level = $_POST["course_level"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $email = $_POST["email"];
    $course = $_POST["course"];
    $address = $_POST["address"];
    $profile_image = 'assets/images/profile.jpg'; // Set default profile image

    if (strlen($password) < 8) {
        $passwordError = "Password must be at least 8 characters long.";
    } else if ($password !== $confirm_password) {
        $passwordError = "Passwords do not match.";
    } else {
        // Check if the account already exists
        $sql_check = "SELECT * FROM users WHERE id_number=? OR email=?";
        $stmt = $conn->prepare($sql_check);
        $stmt->bind_param("ss", $id_number, $email);
        $stmt->execute();
        $result_check = $stmt->get_result();

        if ($result_check->num_rows > 0) {
            $accountError = "An account with this ID number or email already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (id_number, last_name, first_name, middle_name, course_level, password, email, course, address, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $id_number, $last_name, $first_name, $middle_name, $course_level, $hashed_password, $email, $course, $address, $profile_image);

            if ($stmt->execute()) {
                $_SESSION['registration_success'] = true;
                header("Location: login.php");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        }

        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | UC Sit-in Monitoring</title>
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
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
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
        
        .form-container {
            background-color: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transition: all 0.3s ease;
            max-width: 1000px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .registration-header {
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            padding: 2rem;
            color: white;
            position: relative;
        }
        
        .registration-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 15px;
            background-color: white;
            clip-path: ellipse(50% 100% at 50% 100%);
        }
        
        .form-section {
            margin-bottom: 2rem;
            position: relative;
            padding: 1.5rem;
            border-radius: 0.5rem;
        }
        
        .form-section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .form-section-number {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background-color: #4f46e5;
            color: white;
            font-weight: 600;
            margin-right: 0.75rem;
        }
        
        .form-input {
            transition: all 0.2s ease;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            width: 100%;
            background-color: #f9fafb;
            color: #1f2937;
        }
        
        .form-input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
            background-color: white;
        }
        
        .form-input:hover {
            border-color: #d1d5db;
        }
        
        .form-input.error {
            border-color: #ef4444;
        }
        
        .input-label {
            display: block;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .progress-bar {
            height: 0.375rem;
            border-radius: 1rem;
            background: #e5e7eb;
            margin-top: 0.5rem;
        }
        
        .progress-bar-inner {
            height: 100%;
            border-radius: 1rem;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .form-hint {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: #6b7280;
        }
        
        .error-message {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: none;
        }
        
        .input-group {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
        }
        
        .custom-select {
            appearance: none;
            background: #f9fafb url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") no-repeat right 1rem center/1em;
        }
        
        .form-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1.5rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #4f46e5;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4338ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .login-link {
            font-size: 0.875rem;
            color: #6b7280;
            margin-right: auto;
        }
        
        .login-link a {
            color: #4f46e5;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: #f3f4f6;
            border-radius: 0.375rem;
            font-size: 0.75rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.25rem;
            color: #6b7280;
        }
        
        .requirement.met {
            color: #10b981;
        }
        
        .requirement i {
            margin-right: 0.375rem;
            font-size: 0.625rem;
        }
        
        @media (max-width: 640px) {
            body {
                padding: 0;
                background: white;
                display: block;
            }
            
            .form-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            
            .registration-header::after {
                display: none;
            }
        }
        
        /* Animation for form validation feedback */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
        }
    </style>
</head>
<body class="text-gray-800">
    <div class="form-container">
        <div class="registration-header text-center">
            <img src="assets/images/uc-main-logo.jpg" alt="UC Logo" class="w-16 h-16 rounded-full mx-auto mb-3 border-2 border-white shadow-lg">
            <h1 class="text-2xl font-bold mb-1">UC Sit-In Monitoring System</h1>
            <p class="text-indigo-100">Create your account to get started</p>
        </div>
        
        <form id="registrationForm" method="post" action="register.php" class="p-6" novalidate>
            <?php if (isset($passwordError) && !empty($passwordError) || isset($accountError) && !empty($accountError)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            <?php 
                                if (!empty($passwordError)) echo $passwordError;
                                else if (!empty($accountError)) echo $accountError;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Personal Information Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-number">1</div>
                    <h2 class="text-lg font-semibold">Personal Information</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-group">
                        <label for="id_number" class="input-label">ID Number</label>
                        <input type="text" id="id_number" name="id_number" class="form-input" required>
                        <div class="error-message" id="id_number_error">Please enter a valid ID number</div>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-6">
                        <div class="input-group">
                            <label for="email" class="input-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" required>
                            <div class="error-message" id="email_error">Please enter a valid email address</div>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div class="input-group">
                        <label for="first_name" class="input-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" required>
                        <div class="error-message" id="first_name_error">Please enter your first name</div>
                    </div>
                    
                    <div class="input-group">
                        <label for="middle_name" class="input-label">Middle Name <span class="text-gray-400">(Optional)</span></label>
                        <input type="text" id="middle_name" name="middle_name" class="form-input">
                    </div>
                    
                    <div class="input-group">
                        <label for="last_name" class="input-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" required>
                        <div class="error-message" id="last_name_error">Please enter your last name</div>
                    </div>
                </div>
            </div>
            
            <!-- Academic Information Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-number">2</div>
                    <h2 class="text-lg font-semibold">Academic Information</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="input-group">
                        <label for="course" class="input-label">Course</label>
                        <select id="course" name="course" class="form-input custom-select" required>
                            <option value="" selected disabled>Select your course</option>
                            <option value="Computer Science">BS in Computer Science</option>
                            <option value="Information Technology">BS in Information Technology</option>
                            <option value="Software Engineering">BS in Software Engineering</option>
                            <option value="Cybersecurity">BS in Cybersecurity</option>
                        </select>
                        <div class="error-message" id="course_error">Please select your course</div>
                    </div>
                    
                    <div class="input-group">
                        <label for="course_level" class="input-label">Year Level</label>
                        <select id="course_level" name="course_level" class="form-input custom-select" required>
                            <option value="" selected disabled>Select your year level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                        <div class="error-message" id="course_level_error">Please select your year level</div>
                    </div>
                </div>
                
                <div class="input-group mt-6">
                    <label for="address" class="input-label">Home Address</label>
                    <textarea id="address" name="address" class="form-input" rows="3" required></textarea>
                    <div class="error-message" id="address_error">Please enter your home address</div>
                </div>
            </div>
            
            <!-- Password Section -->
            <div class="form-section">
                <div class="form-section-header">
                    <div class="form-section-number">3</div>
                    <h2 class="text-lg font-semibold">Create Password</h2>
                </div>
                
                <div class="input-group">
                    <label for="password" class="input-label">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" class="form-input" required>
                        <span class="input-icon" onclick="togglePasswordVisibility('password')">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    <div class="error-message" id="password_error">Please enter a valid password</div>
                    
                    <div class="progress-bar mt-2">
                        <div id="password-strength" class="progress-bar-inner" style="width: 0%"></div>
                    </div>
                    
                    <div class="password-requirements mt-3">
                        <div class="requirement" id="req-length"><i class="fas fa-circle"></i> At least 8 characters</div>
                        <div class="requirement" id="req-uppercase"><i class="fas fa-circle"></i> At least 1 uppercase letter</div>
                        <div class="requirement" id="req-lowercase"><i class="fas fa-circle"></i> At least 1 lowercase letter</div>
                        <div class="requirement" id="req-number"><i class="fas fa-circle"></i> At least 1 number</div>
                        <div class="requirement" id="req-special"><i class="fas fa-circle"></i> At least 1 special character</div>
                    </div>
                </div>
                
                <div class="input-group mt-4">
                    <label for="confirm_password" class="input-label">Confirm Password</label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <span class="input-icon" onclick="togglePasswordVisibility('confirm_password')">
                            <i class="far fa-eye"></i>
                        </span>
                    </div>
                    <div class="error-message" id="confirm_password_error">Passwords do not match</div>
                </div>
            </div>
            
            <div class="form-footer">
                <div class="login-link">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-user-plus mr-2"></i>
                    Create Account
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                field.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
        
        // Password strength checker
        const password = document.getElementById('password');
        const passwordStrength = document.getElementById('password-strength');
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');

        password.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            // Reset all requirements
            [reqLength, reqUppercase, reqLowercase, reqNumber, reqSpecial].forEach(req => {
                req.classList.remove('met');
                req.querySelector('i').className = 'fas fa-circle';
            });
            
            // Check each requirement
            if (value.length >= 8) {
                strength += 20;
                reqLength.classList.add('met');
                reqLength.querySelector('i').className = 'fas fa-check-circle';
            }
            
            if (value.match(/[A-Z]/)) {
                strength += 20;
                reqUppercase.classList.add('met');
                reqUppercase.querySelector('i').className = 'fas fa-check-circle';
            }
            
            if (value.match(/[a-z]/)) {
                strength += 20;
                reqLowercase.classList.add('met');
                reqLowercase.querySelector('i').className = 'fas fa-check-circle';
            }
            
            if (value.match(/[0-9]/)) {
                strength += 20;
                reqNumber.classList.add('met');
                reqNumber.querySelector('i').className = 'fas fa-check-circle';
            }
            
            if (value.match(/[^A-Za-z0-9]/)) {
                strength += 20;
                reqSpecial.classList.add('met');
                reqSpecial.querySelector('i').className = 'fas fa-check-circle';
            }
            
            // Update strength indicator
            passwordStrength.style.width = strength + '%';
            
            // Update color based on strength
            if (strength < 40) {
                passwordStrength.style.backgroundColor = '#ef4444';  // Red
            } else if (strength < 80) {
                passwordStrength.style.backgroundColor = '#f59e0b';  // Yellow/Orange
            } else {
                passwordStrength.style.backgroundColor = '#10b981';  // Green
            }
        });
        
        // Form validation
        const form = document.getElementById('registrationForm');
        const confirmPassword = document.getElementById('confirm_password');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => {
                el.style.display = 'none';
            });
            
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validate required fields
            document.querySelectorAll('.form-input[required]').forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    const errorElement = document.getElementById(input.id + '_error');
                    if (errorElement) {
                        errorElement.style.display = 'block';
                        input.classList.add('shake');
                        setTimeout(() => {
                            input.classList.remove('shake');
                        }, 600);
                    }
                }
            });
            
            // Validate email format
            const emailInput = document.getElementById('email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailInput.value && !emailPattern.test(emailInput.value)) {
                isValid = false;
                emailInput.classList.add('error');
                document.getElementById('email_error').style.display = 'block';
            }
            
            // Validate password strength
            if (password.value.length < 8 || 
                !password.value.match(/[A-Z]/) || 
                !password.value.match(/[a-z]/) || 
                !password.value.match(/[0-9]/) || 
                !password.value.match(/[^A-Za-z0-9]/)) {
                isValid = false;
                password.classList.add('error');
                document.getElementById('password_error').style.display = 'block';
            }
            
            // Validate password confirmation
            if (password.value !== confirmPassword.value) {
                isValid = false;
                confirmPassword.classList.add('error');
                document.getElementById('confirm_password_error').style.display = 'block';
            }
            
            // Prevent submission if there are errors
            if (!isValid) {
                event.preventDefault();
                // Scroll to first error
                const firstError = document.querySelector('.form-input.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
        
        // Real-time password match validation
        confirmPassword.addEventListener('input', function() {
            if (this.value && password.value !== this.value) {
                this.classList.add('error');
                document.getElementById('confirm_password_error').style.display = 'block';
            } else {
                this.classList.remove('error');
                document.getElementById('confirm_password_error').style.display = 'none';
            }
        });

        <?php
        if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
            echo 'alert("Registration successful! You can now login.");';
            unset($_SESSION['registration_success']);
        }
        ?>
    </script>
</body>
</html>