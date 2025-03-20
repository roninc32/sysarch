<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Sit-In Application</title>
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .nav-container {
            @apply bg-gradient-to-r from-indigo-600 to-blue-500 shadow-lg;
        }
        
        .nav-link {
            @apply px-4 py-2 text-white hover:text-white/90 font-medium transition-all duration-200
                relative after:absolute after:bottom-0 after:left-0 after:w-0 after:h-0.5 
                after:bg-white after:transition-all after:duration-200 hover:after:w-full;
        }

        .glass-morphism {
            @apply backdrop-filter backdrop-blur-lg bg-opacity-90 shadow-xl;
        }

        .hero-section {
            background: linear-gradient(rgba(79, 70, 229, 0.9), rgba(59, 130, 246, 0.9)),
                        url('assets/images/hero-bg.jpg') center/cover;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <header class="w-full top-0 z-50">
        <nav class="nav-container">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex items-center space-x-3 text-white">
                            <img class="h-10 w-auto" src="assets/images/ccs-logo.png" alt="CCS Logo">
                            <span class="text-lg font-bold hidden md:block">CCS Sit-In System</span>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <div class="flex items-baseline space-x-4">
                            <a href="#" class="nav-link">Home</a>
                            <a href="#features" class="nav-link">Features</a>
                            <a href="#how-it-works" class="nav-link">How it Works</a>
                            <a href="login.php" class="px-4 py-2 text-white border-2 border-white/80 rounded-lg 
                                hover:bg-white hover:text-indigo-600 transition-all duration-200">
                                Sign In
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Hero Section -->
    <section class="hero-section min-h-screen flex items-center">
        <div class="container mx-auto px-6 text-center text-white">
            <h1 class="text-5xl md:text-6xl font-bold mb-8">Welcome to CCS Sit-In System</h1>
            <p class="text-xl mb-12 max-w-2xl mx-auto">
                Streamline your academic journey with our efficient sit-in management system.
            </p>
            <div class="space-x-4">
                <a href="register.php" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-bold hover:bg-gray-100 transition-all duration-300">
                    Get Started
                </a>
                <a href="login.php" class="bg-transparent border-2 border-white text-white px-8 py-3 rounded-lg font-bold hover:bg-white hover:text-indigo-600 transition-all duration-300">
                    Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">Key Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
                    <i class="fas fa-calendar-alt text-4xl text-indigo-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-4">Easy Scheduling</h3>
                    <p class="text-gray-600">Schedule sit-in sessions with just a few clicks</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
                    <i class="fas fa-bell text-4xl text-indigo-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-4">Real-time Updates</h3>
                    <p class="text-gray-600">Get instant notifications on request status</p>
                </div>
                <div class="bg-white rounded-lg shadow-lg p-6 glass-morphism">
                    <i class="fas fa-file-alt text-4xl text-indigo-600 mb-4"></i>
                    <h3 class="text-xl font-semibold mb-4">Document Management</h3>
                    <p class="text-gray-600">Easily upload and manage required documents</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="text-center">
                    <div class="bg-blue-600 rounded-full w-12 h-12 flex items-center justify-center text-white mx-auto mb-4">1</div>
                    <h3 class="font-semibold mb-2">Create Account</h3>
                    <p class="text-gray-600">Sign up and complete your profile</p>
                </div>
                <!-- Add steps 2-4 similarly -->
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-20 bg-white">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-12">Contact Us</h2>
            <div class="max-w-lg mx-auto">
                <div class="flex flex-col space-y-4">
                    <p class="text-center text-gray-600 mb-8">
                        Have questions? We're here to help!
                    </p>
                    <div class="flex items-center justify-center space-x-4">
                        <a href="mailto:support@sitin.com" class="text-blue-600 hover:text-blue-800">
                            support@sitin.com
                        </a>
                        <span class="text-gray-300">|</span>
                        <a href="tel:+1234567890" class="text-blue-600 hover:text-blue-800">
                            (123) 456-7890
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">About</h3>
                    <p class="text-gray-400">Making sit-in management easier for everyone.</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Home</a></li>
                        <li><a href="#features" class="hover:text-white">Features</a></li>
                        <li><a href="#how-it-works" class="hover:text-white">How it Works</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white">Terms of Service</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Connect</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">Facebook</a>
                        <a href="#" class="text-gray-400 hover:text-white">Twitter</a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2023 SitIn Application. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
