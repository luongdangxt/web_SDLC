<?php
require_once 'db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT u.*, r.RoleName, r.Permissions 
                             FROM users u 
                             JOIN role r ON u.Role = r.RoleID 
                             WHERE u.Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['Password'])) {
            $_SESSION['user'] = [
                'id' => $user['UserID'],
                'name' => $user['Fullname'],
                'email' => $user['Email'],
                'phone' => $user['Phonenumber'],
                'role' => $user['RoleName'],
                'permissions' => json_decode($user['Permissions'], true)
            ];

            // Redirect based on role
            if ($user['RoleName'] === 'Admin') {
                header('Location: codeweb.php?section=admin');
                exit();
            } elseif ($user['RoleName'] === 'Reception') {
                header('Location: codeweb.php?section=reception');
                exit();
            } else {
                header('Location: codeweb.php?section=home');
                exit();
            }
        } else {
            $error = 'Invalid email or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}

// Check if user is logged in
$currentUser = $_SESSION['user'] ?? null;

// Check if section parameter is set in URL
$section = $_GET['section'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="en">
<!-- Phần còn lại của file HTML -->
<!-- Phần còn lại của file HTML -->



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="fix_console_errors.js"></script>
    <script src="image_handler.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script src="reception_functions.js"></script>
    <style>
        /* Custom CSS for animations and overrides */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .hidden-section {
            display: none;
        }

        /* Custom date picker styling */
        .date-input {
            background-image: url('data:image/svg+xml;utf8,<svg fill="%239CA3AF" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.5rem;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Hide scrollbar for carousel thumbnails */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* Internet Explorer 10+ */
            scrollbar-width: none;  /* Firefox */
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;  /* Safari and Chrome */
        }

        /* Carousel image loading effect */
        #carousel-main-image {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        #carousel-main-image.loaded {
            opacity: 1;
        }

        /* Carousel hover effects */
        #room-images-carousel:hover .group-hover\:opacity-100 {
            opacity: 1;
        }

        /* Room cards hover effects */
        .room-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        /* Line clamp for text truncation */
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Advanced filters animation */
        #advanced-filters {
            transition: all 0.3s ease-in-out;
        }

        /* Search results loading animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans flex flex-col min-h-screen">
    <!-- Header/Navigation -->
    <header class="bg-blue-600 text-white shadow-lg sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-hotel text-2xl"></i>
                <a href="#" class="text-xl font-bold hover:text-blue-200 transition duration-200"
                    onclick="showSection('home')">Hotel Deluxe</a>
            </div>

            <div class="hidden md:flex items-center space-x-6">
                <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('search')">
                    <i class="fas fa-search mr-1"></i> Search Rooms
                </a>
                <div id="auth-links" class="flex items-center space-x-4">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('login')">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('register')">
                        <i class="fas fa-user-plus mr-1"></i> Register
                    </a>
                </div>
                <div id="user-links" class="hidden flex items-center space-x-4">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('book')">
                        <i class="fas fa-calendar-check mr-1"></i> Book Room
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('history')">
                        <i class="fas fa-history mr-1"></i> Booking History
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200 flex items-center gap-2" onclick="showSection('profile')">
                        <img id="nav-avatar" src="https://source.unsplash.com/random/32x32/?portrait" alt="Avatar" 
                             class="w-8 h-8 rounded-full object-cover border-2 border-white">
                        <span id="nav-username">Profile</span>
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="logout()">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
                <div id="admin-links" class="hidden flex items-center space-x-4">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('admin')">
                        <i class="fas fa-tools mr-1"></i> Admin Panel
                    </a>
                </div>
                <div id="reception-links" class="hidden flex items-center space-x-4">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('reception')">
                        <i class="fas fa-concierge-bell mr-1"></i> Reception Panel
                    </a>
                </div>
            </div>

            <button class="md:hidden focus:outline-none" onclick="toggleMobileMenu()">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </nav>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-blue-700 absolute w-full left-0">
            <div class="container mx-auto px-4 py-2 flex flex-col space-y-3">
                <a href="#" class="py-2 hover:text-blue-200 transition duration-200" onclick="showSection('search')">
                    <i class="fas fa-search mr-3"></i> Search Rooms
                </a>
                <div id="auth-links-mobile" class="flex flex-col space-y-3">
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200" onclick="showSection('login')">
                        <i class="fas fa-sign-in-alt mr-3"></i> Login
                    </a>
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200"
                        onclick="showSection('register')">
                        <i class="fas fa-user-plus mr-3"></i> Register
                    </a>
                </div>
                <div id="user-links-mobile" class="hidden flex-col space-y-3">
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200" onclick="showSection('book')">
                        <i class="fas fa-calendar-check mr-3"></i> Book Room
                    </a>
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200"
                        onclick="showSection('history')">
                        <i class="fas fa-history mr-3"></i> Booking History
                    </a>
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200 flex items-center gap-3"
                        onclick="showSection('profile')">
                        <img id="nav-avatar-mobile" src="https://source.unsplash.com/random/32x32/?portrait" alt="Avatar" 
                             class="w-8 h-8 rounded-full object-cover border-2 border-white">
                        <span id="nav-username-mobile">Profile</span>
                    </a>
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200" onclick="logout()">
                        <i class="fas fa-sign-out-alt mr-3"></i> Logout
                    </a>
                </div>
                <div id="admin-links-mobile" class="hidden flex-col space-y-3">
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200" onclick="showSection('admin')">
                        <i class="fas fa-tools mr-3"></i> Admin Panel
                    </a>
                </div>
                <div id="reception-links-mobile" class="hidden flex-col space-y-3">
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200"
                        onclick="showSection('reception')">
                        <i class="fas fa-concierge-bell mr-3"></i> Reception Panel
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <!-- Home Section -->
        <section id="home-section" class="hidden-section">
            <!-- Banner Featured Rooms Carousel -->
            <div id="featured-banner" class="relative h-72 md:h-96 rounded-xl overflow-hidden mb-8 shadow-lg">
                <img id="banner-image" src="" alt="Featured Room"
                    class="w-full h-full object-cover transition-all duration-500">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/70 to-transparent"></div>
                <div class="absolute left-0 top-0 h-full flex flex-col justify-center px-8 z-10">
                    <h2 id="banner-room-name" class="text-3xl md:text-4xl font-bold text-white mb-2"></h2>
                    <div id="banner-room-type"
                        class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium mb-2">
                    </div>
                    <div id="banner-room-desc" class="text-white text-lg mb-4 max-w-xl"></div>
                    <button id="banner-view-btn"
                        class="bg-white text-blue-600 hover:bg-blue-700 hover:text-white px-6 py-2 rounded-lg font-semibold transition duration-200 w-max">
                        View Details <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
                <!-- Carousel controls -->
                <button id="banner-prev"
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-blue-600 rounded-full p-2 shadow z-20">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button id="banner-next"
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-blue-600 rounded-full p-2 shadow z-20">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition duration-200">
                    <div class="text-blue-600 mb-4">
                        <i class="fas fa-wifi text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">High-speed WiFi</h3>
                    <p class="text-gray-600">Stay connected with complimentary high-speed internet access throughout the
                        hotel.</p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition duration-200">
                    <div class="text-blue-600 mb-4">
                        <i class="fas fa-utensils text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Gourmet Dining</h3>
                    <p class="text-gray-600">Enjoy world-class dining experiences with our award-winning restaurants.
                    </p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition duration-200">
                    <div class="text-blue-600 mb-4">
                        <i class="fas fa-spa text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Spa & Wellness</h3>
                    <p class="text-gray-600">Relax and rejuvenate with our luxury spa treatments and wellness programs.
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Available Rooms</h2>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600" id="rooms-count">Loading...</span>
                        <div class="flex gap-2">
                            <button id="grid-view-btn" class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors">
                                <i class="fas fa-th"></i>
                            </button>
                            <button id="list-view-btn" class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="rooms-grid">
                    <!-- Room cards will be populated by JavaScript -->
                </div>
                <div class="hidden" id="rooms-list">
                    <!-- List view will be populated by JavaScript -->
                </div>
                <div class="mt-8 flex justify-center" id="pagination-container">
                    <!-- Pagination will be added here -->
                </div>
            </div>
        </section>





        <!-- Login Section -->
        <section id="login-section" class="hidden-section">
            <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 fade-in">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Login to Your Account</h2>

                <form id="login-form">
                    <div class="mb-4">
                        <label for="login-email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" id="login-email"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div class="mb-6">
                        <label for="login-password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <input type="password" id="login-password"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition duration-200">
                        Login <i class="fas fa-sign-in-alt ml-2"></i>
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <p class="text-gray-600">Don't have an account? <a href="#" class="text-blue-500 hover:underline"
                            onclick="showSection('register')">Register here</a></p>
                </div>
                <div class="mt-4 text-center">
                    <a href="#" class="text-blue-500 hover:underline" onclick="showSection('forgot')">Forgot
                        password?</a>
                </div>
            </div>
        </section>

        <!-- Forgot Password Section -->
        <section id="forgot-section" class="hidden-section">
            <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 fade-in">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Forgot Password</h2>
                <form id="forgot-form">
                    <div class="mb-4">
                        <label for="forgot-email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" id="forgot-email"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition duration-200">
                        Send Reset Link <i class="fas fa-envelope ml-2"></i>
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <a href="#" class="text-blue-500 hover:underline" onclick="showSection('login')">Back to Login</a>
                </div>
            </div>
        </section>

        <!-- Register Section -->
        <section id="register-section" class="hidden-section">
            <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 fade-in">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Create New Account</h2>
                <form id="register-form">
                    <div class="mb-4">
                        <label for="register-name" class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                        <input type="text" id="register-name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="register-email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                        <input type="email" id="register-email"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="register-phone" class="block text-gray-700 text-sm font-bold mb-2">Phone
                            Number</label>
                        <input type="tel" id="register-phone"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="register-password"
                            class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="register-password"
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700"
                                onclick="togglePasswordVisibility('register-password')">
                                <i class="fas fa-eye" id="register-password-icon"></i>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="flex-1 bg-gray-200 rounded-full h-1">
                                    <div class="bg-red-500 h-1 rounded-full transition-all duration-300"
                                        id="password-strength-bar" style="width: 0%"></div>
                                </div>
                                <span id="password-strength-text" class="text-gray-500">Weak</span>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">
                                <div id="password-requirements">
                                    <div class="flex items-center mb-1"><i class="fas fa-circle text-gray-300 mr-1"></i>
                                        At least 8 characters</div>
                                    <div class="flex items-center mb-1"><i class="fas fa-circle text-gray-300 mr-1"></i>
                                        One uppercase letter</div>
                                    <div class="flex items-center mb-1"><i class="fas fa-circle text-gray-300 mr-1"></i>
                                        One lowercase letter</div>
                                    <div class="flex items-center mb-1"><i class="fas fa-circle text-gray-300 mr-1"></i>
                                        One number</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="register-confirm-password"
                            class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="register-confirm-password"
                                class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                            <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700"
                                onclick="togglePasswordVisibility('register-confirm-password')">
                                <i class="fas fa-eye" id="register-confirm-password-icon"></i>
                            </button>
                        </div>
                        <div id="password-match-indicator" class="mt-1 text-xs hidden">
                            <i class="fas fa-check-circle text-green-500 mr-1"></i>
                            <span class="text-green-600">Passwords match</span>
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" id="register-terms"
                                class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" required>
                            <span class="text-sm text-gray-700">
                                I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a>
                                and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                    <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline transition duration-200">
                        Register <i class="fas fa-user-plus ml-2"></i>
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <p class="text-gray-600">Already have an account? <a href="#" class="text-blue-500 hover:underline"
                            onclick="showSection('login')">Login here</a></p>
                </div>
            </div>
        </section>

        <!-- Search Rooms Section -->
        <section id="search-section" class="hidden-section">
            <h2 class="text-2xl font-bold mb-6 text-gray-800">Find Your Perfect Room</h2>

            <div class="bg-white rounded-xl p-6 shadow-md mb-8 fade-in">
                <form id="search-form">
                    <!-- Main search filters -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label for="check-in-date" class="block text-sm font-medium text-gray-700 mb-1">Check-in Date</label>
                            <input type="date" id="check-in-date"
                                class="date-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="check-out-date" class="block text-sm font-medium text-gray-700 mb-1">Check-out Date</label>
                            <input type="date" id="check-out-date"
                                class="date-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Number of Guests</label>
                            <select id="guests"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="1">1 Guest</option>
                                <option value="2" selected>2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5 Guests</option>
                                <option value="6">6+ Guests</option>
                            </select>
                        </div>
                        <div>
                            <label for="room-type" class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                            <select id="room-type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="all">All Types</option>
                                <option value="1">Standard</option>
                                <option value="2">Deluxe</option>
                                <option value="3">Suite</option>
                                <option value="4">Family</option>
                                <option value="5">VIP</option>
                            </select>
                        </div>
                    </div>

                    <!-- Advanced filters -->
                    <div class="border-t pt-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-lg font-semibold text-gray-800">Advanced Filters</h3>
                            <button type="button" id="toggle-advanced-filters" class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-chevron-down mr-1"></i> Show Advanced
                            </button>
                        </div>
                        <div id="advanced-filters" class="hidden grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="price-min" class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
                                <input type="number" id="price-min" placeholder="0"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="price-max" class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
                                <input type="number" id="price-max" placeholder="1000"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="sort-by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                                <select id="sort-by"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="price-low">Price: Low to High</option>
                                    <option value="price-high">Price: High to Low</option>
                                    <option value="name">Room Name</option>
                                    <option value="type">Room Type</option>
                                </select>
                            </div>
                            <div>
                                <label for="amenities" class="block text-sm font-medium text-gray-700 mb-1">Amenities</label>
                                <select id="amenities" multiple
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="wifi">WiFi</option>
                                    <option value="tv">TV</option>
                                    <option value="ac">Air Conditioning</option>
                                    <option value="balcony">Balcony</option>
                                    <option value="ocean-view">Ocean View</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between items-center">
                        <button type="button" id="clear-filters" class="text-gray-600 hover:text-gray-800 font-medium">
                            <i class="fas fa-times mr-1"></i> Clear Filters
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-md font-semibold transition duration-200">
                            <i class="fas fa-search mr-2"></i> Search Rooms
                        </button>
                    </div>
                </form>
            </div>

            <div id="search-results" class="bg-white rounded-xl p-6 shadow-md hidden">
                <h3 class="text-xl font-semibold mb-6 text-gray-800">Available Rooms</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Search results will be populated here -->
                </div>
            </div>
        </section>

        <!-- Book Room Section -->
        <section id="book-section" class="hidden-section">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 fade-in">
                <div id="room-details-container" class="bg-white rounded-xl shadow-md p-6 hidden">
                    <div class="flex justify-between items-start mb-4">
                        <h2 class="text-2xl font-bold text-gray-800" id="book-room-name">Room Details</h2>
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium"
                            id="book-room-type">Type</span>
                    </div>

                    <div class="mb-6 h-64 overflow-hidden rounded-lg">
                        <img src="" alt="Room" id="book-room-image" class="w-full h-full object-cover">
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-gray-500 text-sm mb-1">Max Guests</div>
                            <div class="font-semibold" id="book-room-guests">2</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-gray-500 text-sm mb-1">Price Per Night</div>
                            <div class="font-semibold" id="book-room-price">$150</div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Amenities</h3>
                        <div class="flex flex-wrap gap-2" id="book-room-amenities">
                            <!-- Amenities will be added here -->
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Description</h3>
                        <p class="text-gray-600" id="book-room-description">Lorem ipsum dolor sit amet, consectetur
                            adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold mb-2 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-blue-600"></i> Booking Dates
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <div class="text-sm text-gray-500">Check-in</div>
                                <div class="font-medium" id="book-check-in">-</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Check-out</div>
                                <div class="font-medium" id="book-check-out">-</div>
                            </div>
                            <div class="col-span-2 mt-2">
                                <div class="text-sm text-gray-500">Total Nights</div>
                                <div class="font-medium" id="book-total-nights">-</div>
                            </div>
                            <div class="col-span-2 mt-2">
                                <div class="text-sm text-gray-500">Total Price</div>
                                <div class="text-xl font-bold text-blue-600" id="book-total-price">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="booking-form-container" class="bg-white rounded-xl shadow-md p-6 hidden">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Complete Your Booking</h2>
                    <form id="booking-form">
                        <div class="mb-4">
                            <label for="booking-name" class="block text-gray-700 text-sm font-bold mb-2">Full
                                Name</label>
                            <input type="text" id="booking-name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div class="mb-4">
                            <label for="booking-phone" class="block text-gray-700 text-sm font-bold mb-2">Phone
                                Number</label>
                            <input type="tel" id="booking-phone"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required>
                        </div>
                        <div class="mb-4">
                            <label for="booking-email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                            <input type="email" id="booking-email" value=""
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required readonly>
                        </div>
                        <div class="mb-6">
                            <label for="booking-special-requests"
                                class="block text-gray-700 text-sm font-bold mb-2">Special Requests</label>
                            <textarea id="booking-special-requests" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="mb-6">
                            <label for="booking-payment-method"
                                class="block text-gray-700 text-sm font-bold mb-2">Payment Method</label>
                            <select id="booking-payment-method"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                required onchange="togglePaymentForm()">
                                <option value="pay_later">Pay at Hotel</option>
                                <option value="stripe">Credit/Debit Card (Stripe)</option>
                                <option value="paypal">PayPal</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>

                        <!-- Stripe Payment Form -->
                        <div id="stripe-payment-form" class="mb-6 hidden">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fab fa-stripe mr-2 text-blue-600"></i> Secure Payment with Stripe
                                </h4>
                                <div id="card-element" class="mb-4 p-3 border border-gray-300 rounded-md bg-white">
                                    <!-- Stripe Elements will be inserted here -->
                                </div>
                                <div id="card-errors" class="text-red-600 text-sm mb-4 hidden"></div>
                                <div class="flex items-center text-sm text-gray-600 mb-4">
                                    <i class="fas fa-lock mr-2 text-green-600"></i>
                                    Your payment information is encrypted and secure
                                </div>
                            </div>
                        </div>

                        <!-- PayPal Payment Form -->
                        <div id="paypal-payment-form" class="mb-6 hidden">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fab fa-paypal mr-2 text-blue-600"></i> PayPal Payment
                                </h4>
                                <div id="paypal-button-container" class="mb-4">
                                    <!-- PayPal buttons will be inserted here -->
                                </div>
                                <div class="text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    You will be redirected to PayPal to complete your payment
                                </div>
                            </div>
                        </div>

                        <!-- Bank Transfer Form -->
                        <div id="bank-transfer-form" class="mb-6 hidden">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h4 class="text-lg font-semibold mb-4 flex items-center">
                                    <i class="fas fa-university mr-2 text-blue-600"></i> Bank Transfer Details
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                                        <input type="text" value="Vietcombank"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Account
                                            Number</label>
                                        <input type="text" value="1234567890"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Account
                                            Holder</label>
                                        <input type="text" value="HOTEL DELUXE COMPANY"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100"
                                            readonly>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Transfer
                                            Content</label>
                                        <input type="text" id="bank-transfer-content"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            placeholder="Your booking ID">
                                    </div>
                                </div>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3">
                                    <div class="flex">
                                        <div class="text-yellow-500">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                Please transfer the exact amount and include your booking ID in the
                                                transfer content.
                                                Your booking will be confirmed within 24 hours after payment
                                                verification.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex">
                                <div class="text-yellow-500">
                                    <i class="fas fa-exclamation-circle text-xl"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        You will be charged only upon arrival. Free cancellation is available until 24
                                        hours before check-in.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-md focus:outline-none focus:shadow-outline text-lg transition duration-200">
                            Confirm Booking <i class="fas fa-check-circle ml-2"></i>
                        </button>
                    </form>
                </div>

                <div id="no-room-selected"
                    class="bg-white rounded-xl shadow-md p-10 text-center col-span-1 lg:col-span-2">
                    <div class="text-blue-600 mb-4">
                        <i class="fas fa-hotel text-5xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Start by searching for rooms</h3>
                    <p class="text-gray-500 mb-4">You need to search for available rooms first before you can make a
                        booking.</p>
                    <button onclick="showSection('search')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-semibold transition duration-200">
                        <i class="fas fa-search mr-2"></i> Search Rooms
                    </button>
                </div>
            </div>
        </section>

        <!-- Booking History Section -->
        <section id="history-section" class="hidden-section">
            <div class="bg-white rounded-xl shadow-md overflow-hidden fade-in">
                <div class="p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Your Booking History</h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="booking-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Booking ID</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Room</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Check-in</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Check-out</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total</th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="booking-history-body">
                                <!-- Bookings will be populated here -->
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Login to view your
                                        booking history</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Section -->
        <section id="profile-section" class="hidden-section">
            <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden p-8 fade-in">
                <h2 class="text-2xl font-bold text-center text-gray-800 mb-8">Your Profile</h2>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-1">
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <div
                                class="w-32 h-32 mx-auto rounded-full overflow-hidden mb-4 border-4 border-white shadow-md relative">
                                <img id="profile-avatar" src="https://source.unsplash.com/random/300x300/?portrait" alt="Profile"
                                    class="w-full h-full object-cover" style="image-rendering: auto;">
                                <div class="absolute inset-0 bg-black bg-opacity-0 hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                                    <label for="avatar-upload" class="cursor-pointer text-white opacity-0 hover:opacity-100 transition-opacity duration-200">
                                        <i class="fas fa-camera text-2xl"></i>
                                    </label>
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold" id="profile-display-name">Guest User</h3>
                            <p class="text-gray-600" id="profile-display-email">guest@example.com</p>
                            <div class="mt-4 space-y-2">
                                <label for="avatar-upload" class="text-blue-600 hover:text-blue-800 text-sm font-medium cursor-pointer block">
                                    <i class="fas fa-camera mr-1"></i> Upload Photo
                                </label>
                                <input type="file" id="avatar-upload" accept="image/*" class="hidden" onchange="uploadAvatar(this)">
                                <button type="button" onclick="removeAvatar()" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    <i class="fas fa-trash mr-1"></i> Remove Avatar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-2">
                        <form id="profile-form">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="profile-name" class="block text-gray-700 text-sm font-bold mb-2">Full
                                        Name</label>
                                    <input type="text" id="profile-name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                </div>
                                <div>
                                    <label for="profile-phone" class="block text-gray-700 text-sm font-bold mb-2">Phone
                                        Number</label>
                                    <input type="tel" id="profile-phone"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        required>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="profile-email"
                                    class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                                <input type="email" id="profile-email"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    disabled>
                            </div>

                            <div class="border-t border-gray-200 pt-6 mb-6">
                                <h3 class="text-lg font-semibold mb-4">Change Password</h3>
                                <div class="mb-4">
                                    <label for="profile-current-password"
                                        class="block text-gray-700 text-sm font-bold mb-2">Current Password</label>
                                    <input type="password" id="profile-current-password"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <label for="profile-new-password"
                                            class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                                        <input type="password" id="profile-new-password"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="profile-confirm-password"
                                            class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                                        <input type="password" id="profile-confirm-password"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6 mb-6">
                                <h4 class="text-lg font-semibold mb-4">Account Information</h4>
                                <div id="profile-additional-info" class="bg-gray-50 rounded-lg p-4">
                                    <div class="text-sm text-gray-600">
                                        <p><i class="fas fa-spinner fa-spin mr-2"></i> Loading account information...</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md focus:outline-none focus:shadow-outline transition duration-200">
                                    Save Changes <i class="fas fa-save ml-2"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Admin Panel Section -->
        <section id="admin-section" class="hidden-section">
            <div class="bg-white rounded-xl shadow-md overflow-hidden fade-in">
                <div class="flex flex-col lg:flex-row">
                    <!-- Sidebar -->
                    <div class="lg:w-1/4 bg-gray-800 text-white p-4">
                        <h3 class="text-xl font-bold mb-6 flex items-center">
                            <i class="fas fa-tools mr-2"></i> Admin Panel
                        </h3>
                        <ul class="space-y-2">
                            <li>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg bg-gray-700"
                                    onclick="showAdminTab('users')">
                                    <i class="fas fa-users mr-3"></i> User Management
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-700 transition duration-200"
                                    onclick="showAdminTab('rooms')">
                                    <i class="fas fa-hotel mr-3"></i> Room Management
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-700 transition duration-200"
                                    onclick="showAdminTab('bookings')">
                                    <i class="fas fa-calendar-check mr-3"></i> All Bookings
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-700 transition duration-200"
                                    onclick="showAdminTab('revenue')">
                                    <i class="fas fa-chart-line mr-3"></i> Revenue Reports
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Content -->
                    <div class="lg:w-3/4 p-6">
                        <div id="admin-users-tab">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-users mr-2 text-blue-600"></i> User Management
                            </h3>

                            <div class="mb-6 flex justify-between items-center">
                                <div class="relative w-64">
                                    <input type="text" placeholder="Search users..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="absolute left-3 top-2.5 text-gray-400">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                                    onclick="showUserModal('add')">
                                    <i class="fas fa-plus mr-1"></i> Add User
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Name</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Email</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Role</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="admin-user-table-body">
                                        <!-- User rows will be populated here by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="admin-rooms-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-hotel mr-2 text-blue-600"></i> Room Management
                            </h3>

                            <div class="mb-6 flex justify-between items-center">
                                <div class="relative w-64">
                                    <input type="text" placeholder="Search rooms..."
                                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <div class="absolute left-3 top-2.5 text-gray-400">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                                    onclick="showRoomModal('add')">
                                    <i class="fas fa-plus mr-1"></i> Add Room
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Image</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Price/Night</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Max Guests</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="admin-room-table-body">
                                        <!-- Room rows will be rendered here by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="admin-bookings-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-calendar-check mr-2 text-blue-600"></i> All Bookings
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                                        <select id="booking-date-filter" onchange="loadAdminBookings()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Time</option>
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month">This Month</option>
                                            <option value="year">This Year</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select id="booking-status-filter" onchange="loadAdminBookings()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="checked-in">Checked In</option>
                                            <option value="completed">Completed</option>
                                            <option value="cancelled">Cancelled</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                                        <select id="booking-roomtype-filter" onchange="loadAdminBookings()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Types</option>
                                            <option value="Standard">Standard</option>
                                            <option value="Deluxe">Deluxe</option>
                                            <option value="Suite">Suite</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                        <div class="relative">
                                            <input type="text" id="booking-search-input" placeholder="Search bookings..." 
                                                onkeyup="debounceSearch()" 
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-search"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Booking ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-in</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-out</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="admin-booking-table-body">
                                        <!-- Booking rows will be rendered here by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="admin-revenue-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-chart-line mr-2 text-blue-600"></i> Revenue Reports
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                                        <select id="revenue-period-filter" onchange="loadRevenueReport()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="today">Today</option>
                                            <option value="week">This Week</option>
                                            <option value="month" selected>This Month</option>
                                            <option value="quarter">This Quarter</option>
                                            <option value="year">This Year</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                                        <select id="revenue-roomtype-filter" onchange="loadRevenueReport()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Types</option>
                                            <option value="Standard">Standard</option>
                                            <option value="Deluxe">Deluxe</option>
                                            <option value="Suite">Suite</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Export</label>
                                        <button onclick="exportRevenueReport()" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-download mr-1"></i> Export Report
                                        </button>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Print</label>
                                        <button onclick="printRevenueReport()"
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-print mr-1"></i> Print Report
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8" id="revenue-summary-cards">
                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                            <i class="fas fa-dollar-sign text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                            <p class="text-2xl font-bold text-gray-900" id="total-revenue">$0</p>
                                            <p class="text-sm" id="revenue-growth">Loading...</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                                            <i class="fas fa-calendar-check text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Total Bookings</p>
                                            <p class="text-2xl font-bold text-gray-900" id="total-bookings">0</p>
                                            <p class="text-sm" id="bookings-growth">Loading...</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-yellow-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                            <i class="fas fa-percentage text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Occupancy Rate</p>
                                            <p class="text-2xl font-bold text-gray-900" id="occupancy-rate">0%</p>
                                            <p class="text-sm text-gray-600">Current occupancy</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                            <i class="fas fa-bed text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Avg. Booking Value</p>
                                            <p class="text-2xl font-bold text-gray-900" id="avg-booking-value">$0</p>
                                            <p class="text-sm text-gray-600">Per booking</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Chart -->
                            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                                <h4 class="text-lg font-semibold mb-4">Revenue Trend</h4>
                                <div class="h-64 bg-gray-50 rounded-lg" id="revenue-chart-container">
                                    <canvas id="revenue-chart" width="800" height="256"></canvas>
                                </div>
                            </div>

                            <!-- Top Performing Rooms -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <h4 class="text-lg font-semibold mb-4">Top Performing Rooms</h4>
                                    <div class="space-y-4" id="top-rooms-list">
                                        <div class="text-center py-8 text-gray-500">
                                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                            <p>Loading top performing rooms...</p>
                                            </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <h4 class="text-lg font-semibold mb-4">Revenue by Room Type</h4>
                                    <div class="space-y-4" id="room-type-revenue">
                                        <div class="text-center py-8 text-gray-500">
                                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                            <p>Loading revenue breakdown...</p>
                                                </div>
                                            </div>
                                        </div>
                                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Reception Panel Section -->
        <section id="reception-section" class="hidden-section">
            <div class="bg-white rounded-xl shadow-md overflow-hidden fade-in">
                <div class="flex flex-col lg:flex-row">
                    <!-- Sidebar -->
                    <div class="lg:w-1/4 bg-gray-800 text-white p-4">
                        <h3 class="text-xl font-bold mb-6 flex items-center">
                            <i class="fas fa-concierge-bell mr-2"></i> Reception Panel
                        </h3>
                        <ul class="space-y-2">
                            <li>
                                <a href="#" class="flex items-center px-3 py-2 rounded-lg bg-gray-700"
                                    onclick="showReceptionTab('confirm')">
                                    <i class="fas fa-check-circle mr-3"></i> Confirm Bookings
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-600 transition duration-200"
                                    onclick="showReceptionTab('checkin')">
                                    <i class="fas fa-sign-in-alt mr-3"></i> Check-in Guests
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-600 transition duration-200"
                                    onclick="showReceptionTab('checkout')">
                                    <i class="fas fa-sign-out-alt mr-3"></i> Check-out
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-600 transition duration-200"
                                    onclick="showReceptionTab('status')">
                                    <i class="fas fa-bed mr-3"></i> Room Status
                                </a>
                            </li>
                            <li>
                                <a href="#"
                                    class="flex items-center px-3 py-2 rounded-lg hover:bg-gray-600 transition duration-200"
                                    onclick="showReceptionTab('requests')">
                                    <i class="fas fa-bell mr-3"></i> Guest Requests
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Content -->
                    <div class="lg:w-3/4 p-6">
                        <!-- Confirm Bookings Tab -->
                        <div id="reception-confirm-tab">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-600"></i> Confirm New Bookings
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="confirm-booking-id"
                                            class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                                        <div class="relative">
                                            <input type="text" id="confirm-booking-id" onkeyup="searchConfirmBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="Enter booking ID">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-search"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="confirm-guest-name"
                                            class="block text-sm font-medium text-gray-700 mb-1">Guest Name</label>
                                        <div class="relative">
                                            <input type="text" id="confirm-guest-name" onkeyup="searchConfirmBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"
                                                placeholder="Search by name">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Refresh</label>
                                        <button onclick="loadConfirmBookings()" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-sync mr-1"></i> Refresh List
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Booking ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Contact</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-in</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-out</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Amount</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="confirm-table-body">
                                        <tr>
                                            <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading pending bookings...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-yellow-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-700">
                                            <strong>Note:</strong> Only pending bookings are shown here. Confirm bookings to make them ready for check-in.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Check-in Tab (Modified) -->
                        <div id="reception-checkin-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-sign-in-alt mr-2 text-blue-600"></i> Check-in Confirmed Guests
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="checkin-booking-id"
                                            class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                                        <div class="relative">
                                            <input type="text" id="checkin-booking-id" onkeyup="searchCheckinBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Enter booking ID">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-search"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="checkin-guest-name"
                                            class="block text-sm font-medium text-gray-700 mb-1">Guest Name</label>
                                        <div class="relative">
                                            <input type="text" id="checkin-guest-name" onkeyup="searchCheckinBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Search by name">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Refresh</label>
                                        <button onclick="loadCheckinBookings()" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-sync mr-1"></i> Refresh List
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Booking ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-in</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-out</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="checkin-table-body">
                                        <tr>
                                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading check-in bookings...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- <div class="bg-white border rounded-lg p-6 shadow-sm">
                                <h4 class="text-lg font-semibold mb-4">Check-in Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <h5 class="font-medium text-gray-700 mb-2">Guest Information</h5>
                                        <p class="text-sm text-gray-600">Name: John Doe</p>
                                        <p class="text-sm text-gray-600">Email: john@example.com</p>
                                        <p class="text-sm text-gray-600">Phone: +1 234 567 8900</p>
                                    </div>
                                    <div>
                                        <h5 class="font-medium text-gray-700 mb-2">Booking Details</h5>
                                        <p class="text-sm text-gray-600">Room: Deluxe Room #301</p>
                                        <p class="text-sm text-gray-600">Period: June 12-15, 2023 (3 nights)</p>
                                        <p class="text-sm text-gray-600">Total: $450.00</p>
                                    </div>
                                    <div>
                                        <h5 class="font-medium text-gray-700 mb-2">Check-in Form</h5>
                                        <div class="mb-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                                                <span class="ml-2 text-sm">ID Verified</span>
                                            </label>
                                        </div>
                                        <div class="mb-2">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600">
                                                <span class="ml-2 text-sm">Payment Verified</span>
                                            </label>
                                        </div>
                                        <button
                                            class="mt-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">
                                            <i class="fas fa-key mr-1"></i> Issue Room Key
                                        </button>
                                    </div>
                                </div>
                            </div> -->

                            <div class="bg-white border rounded-lg p-6 shadow-sm mt-6">
                                <h4 class="text-lg font-semibold mb-4">Edit Guest Information</h4>
                                <form id="reception-edit-guest-form" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                        <input type="text" id="reception-guest-edit-name"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md" value="John Doe">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                                        <input type="email" id="reception-guest-edit-email"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            value="john@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-gray-700 text-sm font-bold mb-2">Phone</label>
                                        <input type="tel" id="reception-guest-edit-phone"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                            value="+1 234 567 8900">
                                    </div>
                                    <div class="md:col-span-3 flex justify-end">
                                        <button type="submit"
                                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Save
                                            Changes</button>
                                    </div>
                                </form>
                            </div>

                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mt-6">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            <strong>Note:</strong> Only confirmed bookings are shown here. Guests must be confirmed before they can check-in.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="reception-checkout-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-sign-out-alt mr-2 text-blue-600"></i> Guest Check-out
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label for="checkout-booking-id"
                                            class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                                        <div class="relative">
                                            <input type="text" id="checkout-booking-id" onkeyup="searchCheckoutBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Enter booking ID">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-search"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="checkout-guest-name"
                                            class="block text-sm font-medium text-gray-700 mb-1">Guest Name</label>
                                        <div class="relative">
                                            <input type="text" id="checkout-guest-name" onkeyup="searchCheckoutBookings()"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Search by name">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Refresh</label>
                                        <button onclick="loadCheckoutBookings()" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-sync mr-1"></i> Refresh List
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Booking ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-in</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-out</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total Amount</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="checkout-table-body">
                                        <tr>
                                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading check-out bookings...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="reception-status-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-bed mr-2 text-blue-600"></i> Room Status Board
                            </h3>
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-lg font-semibold">Room Status Overview</h4>
                                    <button onclick="loadRoomStatus()" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                        <i class="fas fa-sync mr-1"></i> Refresh
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6" id="room-status-summary">
                                <div class="bg-white border rounded-lg p-4 shadow-sm">
                                        <div class="text-center">
                                            <p class="text-xs text-gray-500 mb-1">Total</p>
                                            <p class="text-xl font-bold text-gray-900" id="total-rooms">-</p>
                                    </div>
                                        </div>
                                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                        <div class="text-center">
                                            <p class="text-xs text-green-800 mb-1">Available</p>
                                            <p class="text-xl font-bold text-green-600" id="available-rooms">-</p>
                                        </div>
                                        </div>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="text-center">
                                            <p class="text-xs text-blue-800 mb-1">Occupied</p>
                                            <p class="text-xl font-bold text-blue-600" id="occupied-rooms">-</p>
                                        </div>
                                    </div>
                                    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                        <div class="text-center">
                                            <p class="text-xs text-orange-800 mb-1">Reserved</p>
                                            <p class="text-xl font-bold text-orange-600" id="reserved-rooms">-</p>
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <div class="text-center">
                                            <p class="text-xs text-yellow-800 mb-1">Cleaning</p>
                                            <p class="text-xl font-bold text-yellow-600" id="cleaning-rooms">-</p>
                                        </div>
                                    </div>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div class="text-center">
                                            <p class="text-xs text-red-800 mb-1">Maintenance</p>
                                            <p class="text-xl font-bold text-red-600" id="maintenance-rooms">-</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Check-out</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="room-status-table-body">
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading room status...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="reception-requests-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-bell mr-2 text-blue-600"></i> Cleanup Requests
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Filter</label>
                                        <select id="cleanup-status-filter" onchange="loadCleanupRequests()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                        </select>
                        </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Refresh</label>
                                        <button onclick="loadCleanupRequests()" 
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-sync mr-1"></i> Refresh List
                                        </button>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quick Actions</label>
                                        <button onclick="showCreateCleanupModal()" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-plus mr-1"></i> Add Request
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto mb-6">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Request ID</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Room</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Last Guest</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Request Time</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="cleanup-requests-table-body">
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                <i class="fas fa-spinner fa-spin mr-2"></i>Loading cleanup requests...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>© 2025 Hotel Room Booking System</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-blue-300 transition duration-200">Privacy Policy</a>
                    <a href="#" class="hover:text-blue-300 transition duration-200">Contact Us</a>
                    <a href="#" class="hover:text-blue-300 transition duration-200">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full fade-in">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2" id="success-title">Success!</h3>
                <div class="text-sm text-gray-500 mb-4" id="success-message">Your action was completed successfully.
                </div>
            </div>
            <div class="mt-5 sm:mt-6">
                <button type="button" onclick="hideSuccessModal()"
                    class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:text-sm transition duration-200">
                    Continue
                </button>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <!-- User Modal -->
    <div id="user-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4" id="user-modal-title">Add User</h3>
            <form id="user-modal-form">
                <input type="hidden" id="user-modal-id">
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                    <input type="text" id="user-modal-name" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" id="user-modal-email" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" id="user-modal-password" class="w-full px-3 py-2 border rounded-md">
                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current password (when editing)</p>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select id="user-modal-role" class="w-full px-3 py-2 border rounded-md">
                        <option value="Admin">Admin</option>
                        <option value="Reception">Reception</option>
                        <option value="User" selected>User</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideUserModal()"
                        class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Room Modal -->
    <div id="room-modal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-screen overflow-y-auto">
            <h3 class="text-lg font-bold mb-4" id="room-modal-title">Add Room</h3>
            <form id="room-modal-form">
                <input type="hidden" id="room-modal-id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room Number <span class="text-red-500">*</span></label>
                        <input type="text" id="room-modal-name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required 
                               placeholder="e.g. 101, 201A">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room Type <span class="text-red-500">*</span></label>
                        <select id="room-modal-type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Room Type</option>
                            <!-- Options will be loaded by JavaScript -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price per Night ($) <span class="text-red-500">*</span></label>
                        <input type="number" id="room-modal-price" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required 
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Max Guests <span class="text-red-500">*</span></label>
                        <input type="number" id="room-modal-guests" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required 
                               min="1" max="10" placeholder="2">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                        <select id="room-modal-status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="available">Available</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                </div>

                <div id="room-images-container" class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room Images</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-4" id="room-images-preview">
                            <!-- Ảnh sẽ được hiển thị ở đây -->
                        </div>
                        <input type="file" id="room-image-upload" multiple accept="image/*" class="hidden">
                        <button type="button" onclick="document.getElementById('room-image-upload').click()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition-colors">
                            <i class="fas fa-camera mr-2"></i> Add Room Images
                        </button>
                        <p class="text-gray-500 text-xs mt-2">Upload multiple images (JPG, PNG, max 5MB each)</p>
                        <div id="image-upload-progress" class="mt-2 hidden">
                            <div class="bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%" id="progress-bar"></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1" id="progress-text">Uploading images...</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="hideRoomModal()"
                        class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sample data for rooms
        const rooms = [
            {
                id: 1,
                name: "Standard Room",
                type: "standard",
                price: 99,
                maxGuests: 2,
                description: "Cozy and comfortable standard room with all essential amenities for a pleasant stay.",
                amenities: ["WiFi", "TV", "AC", "Coffee Maker"],
                image: "https://ezcloud.vn/wp-content/uploads/2023/03/phong-standard-la-gi.webp"
            },
            {
                id: 2,
                name: "Deluxe Room",
                type: "deluxe",
                price: 149,
                maxGuests: 2,
                description: "Spacious deluxe room with premium furnishings and enhanced amenities for a luxurious experience.",
                amenities: ["WiFi", "TV", "AC", "Mini Bar", "Coffee Maker", "Safe"],
                image: "https://image-tc.galaxy.tf/wijpeg-afu0zj5rhmyyirzditj3g96mk/deluxe-room-king-1-2000px.jpg"
            },
            {
                id: 3,
                name: "Executive Suite",
                type: "suite",
                price: 249,
                maxGuests: 4,
                description: "Elegant suite featuring a separate living area, perfect for business or leisure travelers.",
                amenities: ["WiFi", "TV", "AC", "Mini Bar", "Coffee Maker", "Safe", "Work Desk", "Sofa"],
                image: "https://cdn.marriottnetwork.com/uploads/sites/23/2022/10/Executive-Suite-Living-and-Dining-Rooms-5091-788x650.jpg"
            },
            {
                id: 4,
                name: "Family Room",
                type: "standard",
                price: 179,
                maxGuests: 4,
                description: "Comfortable family room with extra beds, ideal for families traveling with children.",
                amenities: ["WiFi", "TV", "AC", "Coffee Maker", "Extra Beds"],
                image: "https://hips.hearstapps.com/hmg-prod/images/alexander-design-contemporary-family-room-1555953761.jpg"
            },
            {
                id: 5,
                name: "Honeymoon Suite",
                type: "suite",
                price: 299,
                maxGuests: 2,
                description: "Romantic suite with special amenities, designed for newlyweds and couples celebrating special occasions.",
                amenities: ["WiFi", "TV", "AC", "Mini Bar", "Coffee Maker", "Jacuzzi", "King Bed"],
                image: "https://doracruise.com/wp-content/uploads/2020/01/Vip_3-scaled-e1655894984700.jpg"
            },
            {
                id: 6,
                name: "Presidential Suite",
                type: "suite",
                price: 499,
                maxGuests: 2,
                description: "The ultimate in luxury, featuring expansive space, premium furnishings, and exclusive services.",
                amenities: ["WiFi", "TV", "AC", "Mini Bar", "Coffee Maker", "Jacuzzi", "King Bed", "Dining Area", "Separate Living Room"],
                image: "https://cdn.marriottnetwork.com/uploads/sites/23/2022/10/Presidential-Suite-Large-Living-Room-and-Dining-Room-3037-788x650.jpg"
            }
        ];

        // Sample data for bookings
        const bookings = [
            {
                id: "RES-2023-001",
                roomId: 2,
                roomName: "Deluxe Room",
                checkIn: "2023-06-12T14:00:00",
                checkOut: "2023-06-15T11:00:00",
                status: "confirmed",
                guestName: "John Doe",
                totalPrice: 447
            },
            {
                id: "RES-2023-002",
                roomId: 3,
                roomName: "Executive Suite",
                checkIn: "2023-07-10T14:00:00",
                checkOut: "2023-07-15T11:00:00",
                status: "confirmed",
                guestName: "Jane Smith",
                totalPrice: 1245
            }
        ];

        // Current user state (null = not logged in)
        let currentUser = null;

        // Room selected for booking
        let selectedRoom = null;
        let selectedDates = {
            checkIn: null,
            checkOut: null
        };

        // Admin users data
        let adminUsers = [
            { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin', status: 'active' },
            { id: 2, name: 'Reception Staff', email: 'reception@example.com', role: 'reception', status: 'active' },
            { id: 3, name: 'Guest User', email: 'guest@example.com', role: 'user', status: 'active' }
        ];

        // Sample data for admin bookings
        let adminBookings = [
            {
                id: "RES-2023-001",
                guestName: "John Doe",
                guestEmail: "john@example.com",
                roomName: "Deluxe Room #301",
                roomType: "deluxe",
                checkIn: "2023-06-12T14:00:00",
                checkOut: "2023-06-15T11:00:00",
                totalPrice: 447,
                status: "confirmed",
                paymentStatus: "paid",
                createdAt: "2023-06-10T10:30:00"
            },
            {
                id: "RES-2023-002",
                guestName: "Jane Smith",
                guestEmail: "jane@example.com",
                roomName: "Executive Suite #402",
                roomType: "suite",
                checkIn: "2023-07-10T14:00:00",
                checkOut: "2023-07-15T11:00:00",
                totalPrice: 1245,
                status: "confirmed",
                paymentStatus: "paid",
                createdAt: "2023-07-05T15:20:00"
            },
            {
                id: "RES-2023-003",
                guestName: "Mike Johnson",
                guestEmail: "mike@example.com",
                roomName: "Standard Room #201",
                roomType: "standard",
                checkIn: "2023-06-20T14:00:00",
                checkOut: "2023-06-22T11:00:00",
                totalPrice: 198,
                status: "cancelled",
                paymentStatus: "refunded",
                createdAt: "2023-06-18T09:15:00"
            },
            {
                id: "RES-2023-004",
                guestName: "Sarah Wilson",
                guestEmail: "sarah@example.com",
                roomName: "Honeymoon Suite #501",
                roomType: "suite",
                checkIn: "2023-08-01T14:00:00",
                checkOut: "2023-08-05T11:00:00",
                totalPrice: 1196,
                status: "pending",
                paymentStatus: "pending",
                createdAt: "2023-07-25T16:45:00"
            }
        ];

        // DOM Content Loaded
        document.addEventListener('DOMContentLoaded', function () {
            // Kiểm tra tham số section từ URL
            const urlParams = new URLSearchParams(window.location.search);
            const sectionParam = urlParams.get('section');

            if (sectionParam) {
                showSection(sectionParam);
            } else {
                // Mặc định hiển thị trang home
                showSection('home');
            }

            displayFeaturedRooms();

            // Set min dates for date inputs
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('check-in-date').min = today;
            document.getElementById('check-out-date').min = today;

            // Form event listeners
            document.getElementById('login-form').addEventListener('submit', handleLogin);
            document.getElementById('register-form').addEventListener('submit', handleRegister);

            // Real-time validation for registration form
            document.getElementById('register-password').addEventListener('input', function () {
                updatePasswordStrength(this.value);
                checkPasswordMatch();
            });

            document.getElementById('register-confirm-password').addEventListener('input', function () {
                checkPasswordMatch();
            });

            document.getElementById('register-email').addEventListener('blur', function () {
                if (this.value && !validateEmail(this.value)) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });

            document.getElementById('register-phone').addEventListener('blur', function () {
                if (this.value && !validatePhone(this.value)) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });
            document.getElementById('search-form').addEventListener('submit', handleSearch);
            document.getElementById('booking-form').addEventListener('submit', handleBooking);
            document.getElementById('profile-form').addEventListener('submit', handleProfileUpdate);
            // Fix for forgot password form - check if element exists first
        const forgotForm = document.getElementById('forgot-form');
        if (forgotForm) {
            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = document.getElementById('forgot-email').value;
                if (email) {
                    showSuccessModal('Reset Link Sent', 'If an account with this email exists, a password reset link has been sent.');
                } else {
                    alert('Please enter your email address.');
                }
            });
        }

            // Initialize datepicker on check-in date input
            document.getElementById('check-in-date').addEventListener('change', function () {
                const checkInDate = this.value;
                document.getElementById('check-out-date').min = checkInDate;
                selectedDates.checkIn = checkInDate;
                updateBookingSummary();
            });

            // Initialize datepicker on check-out date input
            document.getElementById('check-out-date').addEventListener('change', function () {
                selectedDates.checkOut = this.value;
                updateBookingSummary();
            });

            // Check if there's a stored user
            try {
                const storedUser = localStorage.getItem('hotelBookingUser');
                if (storedUser) {
                    currentUser = JSON.parse(storedUser);
                    if (typeof updateUIForUser === 'function') {
                        updateUIForUser();
                    }
                }
            } catch (error) {
                console.warn('Error loading stored user:', error);
                localStorage.removeItem('hotelBookingUser');
            }

            if (document.getElementById('reception-edit-guest-form')) {
                document.getElementById('reception-edit-guest-form').addEventListener('submit', function (e) {
                    e.preventDefault();
                    showSuccessModal('Guest Updated', 'Guest information has been updated (demo only).');
                });
            }

            // Admin user management
            renderAdminUsers();
            document.querySelector('#admin-users-tab input[type="text"]').addEventListener('input', function () {
                renderAdminUsers(this.value);
            });
            document.getElementById('user-modal-form').addEventListener('submit', function (e) {
                e.preventDefault();
                const id = document.getElementById('user-modal-id').value;
                const name = document.getElementById('user-modal-name').value;
                const email = document.getElementById('user-modal-email').value;
                const password = document.getElementById('user-modal-password').value;
                const role = document.getElementById('user-modal-role').value;
                const status = document.getElementById('user-modal-status').value;
                if (id) {
                    // Edit
                    const user = adminUsers.find(u => u.id == id);
                    if (user) {
                        user.name = name; user.email = email; user.role = role; user.status = status;
                        if (password) user.password = password; // chỉ cập nhật nếu nhập mật khẩu mới
                    }
                } else {
                    // Add
                    adminUsers.push({ id: Date.now(), name, email, role, status, password });
                }
                hideUserModal();
                renderAdminUsers();
                showSuccessModal('User Saved', 'User information has been saved.');
            });

            // Admin room management
            renderAdminRooms();
            document.querySelector('#admin-rooms-tab input[type="text"]').addEventListener('input', function () {
                renderAdminRooms(this.value);
            });
            document.getElementById('room-modal-form').addEventListener('submit', function (e) {
                e.preventDefault();
                const id = document.getElementById('room-modal-id').value;
                const name = document.getElementById('room-modal-name').value;
                const type = document.getElementById('room-modal-type').value;
                const price = parseInt(document.getElementById('room-modal-price').value);
                const maxGuests = parseInt(document.getElementById('room-modal-guests').value);
                const image = document.getElementById('room-modal-image').value;
                const description = document.getElementById('room-modal-description').value;
                const status = document.getElementById('room-modal-status').value;

                // Get selected amenities
                const amenities = [];
                document.querySelectorAll('#room-modal-form input[type="checkbox"]:checked').forEach(checkbox => {
                    amenities.push(checkbox.value);
                });

                if (id) {
                    // Edit room
                    const room = rooms.find(r => r.id == id);
                    if (room) {
                        room.name = name;
                        room.type = type;
                        room.price = price;
                        room.maxGuests = maxGuests;
                        room.image = image;
                        room.description = description;
                        room.amenities = amenities;
                    }
                } else {
                    // Add new room
                    const newRoom = {
                        id: Date.now(),
                        name: name,
                        type: type,
                        price: price,
                        maxGuests: maxGuests,
                        image: image,
                        description: description,
                        amenities: amenities
                    };
                    rooms.push(newRoom);
                }
                hideRoomModal();
                renderAdminRooms();
                showSuccessModal('Room Saved', 'Room information has been saved.');
            });

            // Admin booking management
            renderAdminBookings();
        });


        // Enhanced image upload handler
        document.getElementById('room-image-upload').addEventListener('change', function (e) {
            const files = Array.from(e.target.files);
            const previewContainer = document.getElementById('room-images-preview');
            
            // Don't clear existing images if editing - append new ones
            if (!document.getElementById('room-modal-id').value) {
                previewContainer.innerHTML = '';
            }

            if (files.length === 0) return;

            // Validate files
            const validFiles = files.filter(file => {
                if (!file.type.startsWith('image/')) {
                    showErrorModal('Invalid File', `${file.name} is not an image file`);
                    return false;
                }
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showErrorModal('File Too Large', `${file.name} is larger than 5MB`);
                    return false;
                }
                return true;
            });

            if (validFiles.length === 0) return;

            // Show progress
            const progressContainer = document.getElementById('image-upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            progressContainer.classList.remove('hidden');
            progressText.textContent = `Processing ${validFiles.length} image(s)...`;

            let processedCount = 0;
            validFiles.forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function (e) {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'relative group';
                    imgContainer.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-20 object-cover rounded-lg border border-gray-200 group-hover:opacity-75 transition-opacity">
                        <button type="button" class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity" 
                                onclick="this.parentElement.remove(); updateImageCount();" title="Remove image">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="absolute bottom-1 left-1 bg-black bg-opacity-50 text-white text-xs px-1 rounded">
                            ${file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name}
                        </div>
                    `;
                    previewContainer.appendChild(imgContainer);

                    processedCount++;
                    const progress = (processedCount / validFiles.length) * 100;
                    progressBar.style.width = progress + '%';
                    
                    if (processedCount === validFiles.length) {
                        setTimeout(() => {
                            progressContainer.classList.add('hidden');
                            progressBar.style.width = '0%';
                            updateImageCount();
                        }, 500);
                    }
                };

                reader.readAsDataURL(file);
            });

            // Clear the input for re-upload
            this.value = '';
        });

        // Update image count display
        function updateImageCount() {
            const imageCount = document.querySelectorAll('#room-images-preview img').length;
            const button = document.querySelector('#room-images-container button');
            if (imageCount > 0) {
                button.innerHTML = `<i class="fas fa-camera mr-2"></i> Add More Images (${imageCount} added)`;
            } else {
                button.innerHTML = `<i class="fas fa-camera mr-2"></i> Add Room Images`;
            }
        }

        // Room management functions - Sửa lại để gọi API thực tế
        // Room management functions - Sửa lại để gọi API thực tế
        function renderAdminRooms(filter = '') {
            const tbody = document.getElementById('admin-room-table-body');

            // Show loading state
            tbody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading rooms...
            </td>
        </tr>
    `;

            // Build URL with filter parameter if needed
            let url = 'admin_get_rooms.php';
            if (filter) {
                url += `?search=${encodeURIComponent(filter)}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(rooms => {
                    if (rooms.error) throw new Error(rooms.error);

                    if (filter) {
                        rooms = rooms.filter(r =>
                            r.RoomNumber.toLowerCase().includes(filter.toLowerCase()) ||
                            r.TypeName.toLowerCase().includes(filter.toLowerCase())
                        );
                    }

                    tbody.innerHTML = rooms.length ? '' : `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        No rooms found
                    </td>
                </tr>
            `;

                    rooms.forEach(room => {
                        tbody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.RoomID}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            ${room.PrimaryImage ?
                                `<img src="${room.PrimaryImage}" alt="Room ${room.RoomNumber}" style="width:60px; height:40px; object-fit:cover; border-radius:6px;">` :
                                `<i class="fas fa-image text-gray-300" style="font-size:40px;"></i>`}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.RoomNumber}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.TypeName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${room.Price}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.Capacity}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${room.Status === 'available' ? 'bg-green-100 text-green-800' : room.Status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                ${room.Status.charAt(0).toUpperCase() + room.Status.slice(1)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="showRoomModal('edit', ${room.RoomID})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-red-600 hover:text-red-900" onclick="deleteRoom(${room.RoomID})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Error loading rooms: ${error.message}
                    </td>
                </tr>
            `;
                });
        }


        // Hàm load room types từ API

        // Hiển thị modal thêm/sửa phòng
        function showRoomModal(mode, id = null) {
            const modal = document.getElementById('room-modal');
            const title = document.getElementById('room-modal-title');
            const form = document.getElementById('room-modal-form');

            modal.classList.remove('hidden');
            title.textContent = mode === 'add' ? 'Add Room' : 'Edit Room';
            form.reset();
            document.getElementById('room-modal-id').value = id || '';
            document.getElementById('room-images-preview').innerHTML = ''; // Clear image previews

            // Load room types first
            loadRoomTypes().then(() => {
                // Nếu là chế độ edit, load dữ liệu phòng sau khi đã load room types
                if (mode === 'edit' && id) {
                    fetch(`admin_get_room.php?id=${id}`)
                        .then(response => response.json())
                        .then(room => {
                            if (room.error) throw new Error(room.error);

                            document.getElementById('room-modal-name').value = room.RoomNumber || '';
                            document.getElementById('room-modal-type').value = room.RoomTypeID || '';
                            document.getElementById('room-modal-price').value = room.Price || '';
                            document.getElementById('room-modal-status').value = room.Status || 'available';

                            // Hiển thị ảnh nếu có
                            const imagesContainer = document.getElementById('room-images-preview');
                            if (room.images && room.images.length > 0) {
                                room.images.forEach(image => {
                                    const imgContainer = document.createElement('div');
                                    imgContainer.className = 'relative';
                                    imgContainer.innerHTML = `
                                <img src="${image.ImageURL}" class="w-full h-24 object-cover rounded">
                                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs" onclick="this.parentElement.remove()">
                                    
                                </button>
                            `;
                                    imagesContainer.appendChild(imgContainer);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error loading room:', error);
                            showErrorModal('Error', 'Failed to load room data');
                        });
                }
            }).catch(error => {
                console.error('Error loading room types:', error);
                showErrorModal('Error', 'Failed to load room types');
            });
        }

        // Hàm load room types từ API
        function loadRoomTypes() {
            return new Promise((resolve) => {
                const roomTypeSelect = document.getElementById('room-modal-type');
                roomTypeSelect.innerHTML = '<option value="">Select Room Type</option>';

                // Hard-code các loại phòng dựa trên bảng roomtype bạn cung cấp
                const roomTypes = [
                    { RoomTypeID: 1, TypeName: 'Standard' },
                    { RoomTypeID: 2, TypeName: 'Deluxe' },
                    { RoomTypeID: 3, TypeName: 'Suite' },
                    { RoomTypeID: 4, TypeName: 'Family' },
                    { RoomTypeID: 5, TypeName: 'VIP' }
                ];

                roomTypes.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.RoomTypeID;
                    option.textContent = type.TypeName;
                    roomTypeSelect.appendChild(option);
                });

                resolve();
            });
        }

        // Xử lý thêm/sửa phòng
        document.getElementById('room-modal-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const id = document.getElementById('room-modal-id').value;
            const roomNumber = document.getElementById('room-modal-name').value;
            const roomTypeID = document.getElementById('room-modal-type').value;
            const price = document.getElementById('room-modal-price').value;
            const capacity = document.getElementById('room-modal-guests').value;
            const status = document.getElementById('room-modal-status').value;

            // Get images from preview
            const images = [];
            const imageElements = document.querySelectorAll('#room-images-preview img');
            imageElements.forEach(img => {
                images.push({
                    url: img.src,
                    caption: '' // You can add caption input fields if needed
                });
            });

            // Validate
            if (!roomNumber || !roomTypeID || !price || !capacity) {
                showErrorModal('Validation Error', 'Please fill in all required fields');
                return;
            }

            const roomData = {
                roomNumber: roomNumber,
                roomTypeID: roomTypeID,
                price: parseFloat(price),
                capacity: parseInt(capacity),
                status: status,
                images: images
            };

            if (id) roomData.roomID = id;

            const url = id ? 'admin_update_room.php' : 'admin_add_room.php';
            const method = id ? 'PUT' : 'POST';

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(roomData)
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Request failed');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        hideRoomModal();
                        renderAdminRooms();
                        showSuccessModal('Success', data.message || (id ? 'Room updated successfully' : 'Room added successfully'));
                    } else {
                        throw new Error(data.message || 'Operation failed');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Error', error.message || 'An error occurred');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
        });

        // Xóa phòng
        function deleteRoom(id) {
            if (!confirm('Are you sure you want to delete this room?')) return;

            fetch('admin_delete_room.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ roomID: id })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminRooms();
                        showSuccessModal('Success', data.message || 'Room deleted successfully');
                    } else {
                        throw new Error(data.message || 'Failed to delete room');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Error', error.message || 'An error occurred while deleting room');
                });
        }

// Cập nhật giao diện modal phòng
// Thêm phần này vào HTML modal (trong phần room-modal)
/*
<div id="room-images-container" class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2">Room Images</label>
    <div class="grid grid-cols-3 gap-2 mb-2" id="room-images-preview">
        <!-- Ảnh sẽ được hiển thị ở đây -->
    </div >
            <input type="file" id="room-image-upload" multiple accept="image/*" class="hidden">
                <button type="button" onclick="document.getElementById('room-image-upload').click()"
                    class="bg-blue-100 text-blue-600 px-3 py-1 rounded text-sm">
                    <i class="fas fa-plus mr-1"></i> Add Images
                </button>
            </div>
            */



        // Show a specific section
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('section').forEach(section => {
                section.classList.add('hidden-section');
            });

            // Show the requested section
            const section = document.getElementById(`${sectionId}-section`);
            if (section) {
                section.classList.remove('hidden-section');
                section.classList.add('fade-in');

                // Special case handling for admin/reception sections
                if (sectionId === 'admin') {
                    showAdminTab('users'); // Mặc định hiển thị tab Users
                } else if (sectionId === 'reception') {
                    showReceptionTab('confirm'); // Mặc định hiển thị tab Confirm Bookings
                }

                // Special case handling for the home section
                if (sectionId === 'home') {
                    displayFeaturedRooms();
                }

                // Special case handling for the search section
                if (sectionId === 'search') {
                    setupAdvancedFilters();
                    setupSearchForm();
                }

                // Special case for booking history
                if (sectionId === 'history' && currentUser) {
                    displayBookingHistory();
                }

                // Special case for profile section
                if (sectionId === 'profile' && currentUser) {
                    loadProfileData();
                }
            }

            // Scroll to top
            window.scrollTo(0, 0);
        }

        // Show admin tab
        function showAdminTab(tabId) {
            // Ẩn tất cả các tab
            document.querySelectorAll('[id^="admin-"][id$="-tab"]').forEach(tab => {
                tab.classList.add('hidden');
            });
            // Hiện tab được chọn
            document.getElementById(`admin-${tabId}-tab`).classList.remove('hidden');

            // Cập nhật trạng thái active cho sidebar
            document.querySelectorAll('#admin-section ul li a').forEach(link => {
                link.classList.remove('bg-gray-700');
                link.classList.add('hover:bg-gray-700');
            });
            event.currentTarget.classList.remove('hover:bg-gray-700');
            event.currentTarget.classList.add('bg-gray-700');

            // Load data khi chuyển tab
            if (tabId === 'users') renderAdminUsers();
            if (tabId === 'rooms') renderAdminRooms();
            if (tabId === 'bookings') loadAdminBookings();
            if (tabId === 'revenue') loadRevenueReport();
        }

        // Show reception tab
        function showReceptionTab(tabId) {
            // Ẩn tất cả các tab
            document.querySelectorAll('[id^="reception-"][id$="-tab"]').forEach(tab => {
                tab.classList.add('hidden');
            });
            // Hiện tab được chọn
            document.getElementById(`reception-${tabId}-tab`).classList.remove('hidden');

            // Cập nhật trạng thái active cho sidebar
            document.querySelectorAll('#reception-section ul li a').forEach(link => {
                link.classList.remove('bg-gray-700');
                link.classList.add('hover:bg-gray-700');
            });
            event.currentTarget.classList.remove('hover:bg-gray-700');
            event.currentTarget.classList.add('bg-gray-700');

            // Load data khi chuyển tab
            if (tabId === 'confirm') loadConfirmBookings();
            if (tabId === 'checkin') loadCheckinBookings();
            if (tabId === 'checkout') loadCheckoutBookings(); 
            if (tabId === 'status') loadRoomStatus();
            if (tabId === 'requests') loadCleanupRequests();
        }

        // Toggle mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Display all available rooms on home page with improved layout
        let allRooms = [];
        let currentPage = 1;
        let roomsPerPage = 8;
        let currentView = 'grid';

        function displayFeaturedRooms() {
            const roomsGrid = document.getElementById('rooms-grid');
            const roomsList = document.getElementById('rooms-list');
            const roomsCount = document.getElementById('rooms-count');
            
            if (!roomsGrid) return;

            // Show loading state
            roomsGrid.innerHTML = `
                <div class="col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4 text-center py-10">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Loading rooms...
                </div>
            `;

            // Fetch all available rooms from API
            fetch('get_featured_rooms.php')
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Failed to load rooms');

                    allRooms = data.rooms;
                    updateRoomsCount();
                    renderRooms();
                    setupViewToggle();
                    setupPagination();
                })
                .catch(error => {
                    console.error('Error:', error);
                    roomsGrid.innerHTML = `
                        <div class="col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4 text-center py-10 text-red-500">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Error loading rooms: ${error.message}
                        </div>
                    `;
                });
        }

        function updateRoomsCount() {
            const roomsCount = document.getElementById('rooms-count');
            if (roomsCount) {
                roomsCount.textContent = `${allRooms.length} rooms available`;
            }
        }

        function renderRooms() {
            const startIndex = (currentPage - 1) * roomsPerPage;
            const endIndex = startIndex + roomsPerPage;
            const roomsToShow = allRooms.slice(startIndex, endIndex);

            if (currentView === 'grid') {
                renderGridView(roomsToShow);
            } else {
                renderListView(roomsToShow);
            }
        }

        function renderGridView(rooms) {
            const roomsGrid = document.getElementById('rooms-grid');
            const roomsList = document.getElementById('rooms-list');
            
            roomsList.classList.add('hidden');
            roomsGrid.classList.remove('hidden');

            if (rooms.length === 0) {
                roomsGrid.innerHTML = `
                    <div class="col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4 text-center py-10">
                        No rooms available
                    </div>
                `;
                return;
            }

            roomsGrid.innerHTML = '';
            rooms.forEach(room => {
                const roomCard = document.createElement('div');
                roomCard.className = 'bg-white rounded-xl overflow-hidden shadow-md room-card transition duration-200 cursor-pointer hover:shadow-lg';
                roomCard.innerHTML = `
                    <div class="relative">
                        <img src="${room.PrimaryImage || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                             alt="Room ${room.RoomNumber}" 
                             class="w-full h-48 object-cover"
                             onclick="viewRoomDetails(${room.RoomID})">
                        <div class="absolute top-2 right-2">
                            <span class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-medium">$${room.Price}</span>
                        </div>
                    </div>
                    <div class="p-4" onclick="viewRoomDetails(${room.RoomID})">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-gray-800">Room ${room.RoomNumber}</h3>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${room.TypeName}</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">${room.TypeDescription || 'No description available'}</p>
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                            <span><i class="fas fa-users mr-1"></i> ${room.Capacity || 2} guests</span>
                            <span><i class="fas fa-star text-yellow-400 mr-1"></i> 4.5</span>
                        </div>
                        <button onclick="viewRoomDetails(${room.RoomID})" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition duration-200">
                            View Details <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                `;
                roomsGrid.appendChild(roomCard);
            });
        }

        function renderListView(rooms) {
            const roomsGrid = document.getElementById('rooms-grid');
            const roomsList = document.getElementById('rooms-list');
            
            roomsGrid.classList.add('hidden');
            roomsList.classList.remove('hidden');

            if (rooms.length === 0) {
                roomsList.innerHTML = `
                    <div class="text-center py-10">
                        No rooms available
                    </div>
                `;
                return;
            }

            roomsList.innerHTML = '';
            rooms.forEach(room => {
                const roomItem = document.createElement('div');
                roomItem.className = 'bg-white rounded-lg shadow-md p-4 mb-4 hover:shadow-lg transition duration-200 cursor-pointer';
                roomItem.innerHTML = `
                    <div class="flex items-center space-x-4" onclick="viewRoomDetails(${room.RoomID})">
                        <img src="${room.PrimaryImage || 'https://via.placeholder.com/150x100?text=No+Image'}" 
                             alt="Room ${room.RoomNumber}" 
                             class="w-24 h-16 object-cover rounded-lg">
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <h3 class="text-lg font-bold text-gray-800">Room ${room.RoomNumber}</h3>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${room.TypeName}</span>
                            </div>
                            <p class="text-gray-600 text-sm mt-1">${room.TypeDescription || 'No description available'}</p>
                            <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500">
                                <span><i class="fas fa-users mr-1"></i> ${room.Capacity || 2} guests</span>
                                <span><i class="fas fa-star text-yellow-400 mr-1"></i> 4.5</span>
                                <span class="text-blue-600 font-semibold">$${room.Price}/night</span>
                            </div>
                        </div>
                        <button onclick="viewRoomDetails(${room.RoomID})" 
                                class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition duration-200">
                            View Details
                        </button>
                    </div>
                `;
                roomsList.appendChild(roomItem);
            });
        }

        function setupViewToggle() {
            const gridViewBtn = document.getElementById('grid-view-btn');
            const listViewBtn = document.getElementById('list-view-btn');

            if (gridViewBtn && listViewBtn) {
                gridViewBtn.onclick = () => {
                    currentView = 'grid';
                    gridViewBtn.className = 'p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors';
                    listViewBtn.className = 'p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors';
                    renderRooms();
                };

                listViewBtn.onclick = () => {
                    currentView = 'list';
                    listViewBtn.className = 'p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-colors';
                    gridViewBtn.className = 'p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors';
                    renderRooms();
                };
            }
        }

        function setupPagination() {
            const totalPages = Math.ceil(allRooms.length / roomsPerPage);
            const paginationContainer = document.getElementById('pagination-container');
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }

            let paginationHTML = `
                <div class="flex items-center space-x-2">
                    <button onclick="changePage(${currentPage - 1})" 
                            class="px-3 py-2 rounded-lg ${currentPage <= 1 ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'}"
                            ${currentPage <= 1 ? 'disabled' : ''}>
                        <i class="fas fa-chevron-left"></i>
                    </button>
            `;

            for (let i = 1; i <= totalPages; i++) {
                if (i === currentPage) {
                    paginationHTML += `<button class="px-3 py-2 bg-blue-600 text-white rounded-lg">${i}</button>`;
                } else if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                    paginationHTML += `<button onclick="changePage(${i})" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-blue-600 hover:text-white">${i}</button>`;
                } else if (i === currentPage - 2 || i === currentPage + 2) {
                    paginationHTML += `<span class="px-2 text-gray-500">...</span>`;
                }
            }

            paginationHTML += `
                <button onclick="changePage(${currentPage + 1})" 
                        class="px-3 py-2 rounded-lg ${currentPage >= totalPages ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'}"
                        ${currentPage >= totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>`;

            paginationContainer.innerHTML = paginationHTML;
        }

        function changePage(page) {
            const totalPages = Math.ceil(allRooms.length / roomsPerPage);
            if (page >= 1 && page <= totalPages) {
                currentPage = page;
                renderRooms();
                setupPagination();
            }
        }

        // Advanced filters functionality
        function setupAdvancedFilters() {
            const toggleBtn = document.getElementById('toggle-advanced-filters');
            const advancedFilters = document.getElementById('advanced-filters');
            const clearFiltersBtn = document.getElementById('clear-filters');

            if (toggleBtn && advancedFilters) {
                toggleBtn.onclick = () => {
                    const isHidden = advancedFilters.classList.contains('hidden');
                    advancedFilters.classList.toggle('hidden');
                    toggleBtn.innerHTML = isHidden ? 
                        '<i class="fas fa-chevron-up mr-1"></i> Hide Advanced' : 
                        '<i class="fas fa-chevron-down mr-1"></i> Show Advanced';
                };
            }

            if (clearFiltersBtn) {
                clearFiltersBtn.onclick = () => {
                    // Clear all filter inputs
                    document.getElementById('price-min').value = '';
                    document.getElementById('price-max').value = '';
                    document.getElementById('sort-by').value = 'price-low';
                    document.getElementById('amenities').selectedIndex = -1;
                    document.getElementById('room-type').value = 'all';
                    document.getElementById('guests').value = '2';
                };
            }
        }

        // Search functionality
        function setupSearchForm() {
            const searchForm = document.getElementById('search-form');
            if (searchForm) {
                searchForm.onsubmit = (e) => {
                    e.preventDefault();
                    performSearch();
                };
            }
        }

        function performSearch() {
            const checkInDate = document.getElementById('check-in-date').value;
            const checkOutDate = document.getElementById('check-out-date').value;
            const guests = parseInt(document.getElementById('guests').value);
            const roomType = document.getElementById('room-type').value;
            const priceMin = document.getElementById('price-min').value;
            const priceMax = document.getElementById('price-max').value;
            const sortBy = document.getElementById('sort-by').value;

            // Validate guests
            if (guests < 1 || guests > 10) {
                alert('Number of guests must be between 1 and 10');
                return;
            }

            // For testing purposes, allow search without dates
            if (!checkInDate || !checkOutDate) {
                console.log('Searching without date validation for testing');
                // Don't return, continue with search
            } else if (new Date(checkInDate) >= new Date(checkOutDate)) {
                alert('Check-out date must be after check-in date');
                return;
            }

            // Show loading state
            const searchResults = document.getElementById('search-results');
            searchResults.classList.remove('hidden');
            searchResults.querySelector('.grid').innerHTML = `
                <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-10">
                    <i class="fas fa-spinner fa-spin mr-2"></i> Searching for available rooms...
                </div>
            `;

            // Build search parameters
            const searchParams = new URLSearchParams({
                check_in: checkInDate,
                check_out: checkOutDate,
                guests: guests,
                room_type: roomType,
                price_min: priceMin,
                price_max: priceMax,
                sort_by: sortBy
            });

            // Perform search using search_rooms.php API
            console.log('Performing search with params:', searchParams.toString());
            fetch(`search_rooms.php?${searchParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Search failed');
                    
                    // Debug information
                    console.log('Search API Response:', data);
                    console.log('Rooms returned:', data.rooms.length);
                    if (data.debug) {
                        console.log('Debug info:', data.debug);
                    }
                    
                    // Check if this is a "no search criteria" response
                    if (data.message && data.rooms.length === 0) {
                        searchResults.querySelector('.grid').innerHTML = `
                            <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-10 text-gray-600">
                                <i class="fas fa-search mr-2"></i> ${data.message}
                                <div class="mt-2 text-sm text-gray-500">
                                    Please enter your search criteria (dates, number of guests, room type, or price range) to find available rooms.
                                </div>
                            </div>
                        `;
                        return;
                    }
                    
                    // Log capacity information for debugging
                    if (data.rooms && data.rooms.length > 0) {
                        console.log('Room capacities:', data.rooms.map(r => ({ 
                            room: r.RoomNumber, 
                            capacity: r.Capacity, 
                            type: r.TypeName 
                        })));
                    }

                    // Show debug information in a more visible way
                    if (data.debug) {
                        console.log('=== SEARCH DEBUG INFO ===');
                        console.log('Guests requested:', data.debug.guests_requested);
                        console.log('Filtered rooms:', data.debug.filtered_rooms);
                        console.log('SQL Query:', data.debug.query);
                        console.log('SQL Parameters:', data.debug.params);
                        console.log('=== END DEBUG INFO ===');
                    }
                    
                    displaySearchResults(data.rooms);
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.querySelector('.grid').innerHTML = `
                        <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-10 text-red-500">
                            <i class="fas fa-exclamation-triangle mr-2"></i> Search error: ${error.message}
                        </div>
                    `;
                });
        }

        function displaySearchResults(rooms) {
            const searchResults = document.getElementById('search-results');
            const grid = searchResults.querySelector('.grid');
            
            // Make sure search results section is visible
            searchResults.classList.remove('hidden');

            if (rooms.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-1 md:col-span-2 lg:col-span-3 text-center py-10">
                        <i class="fas fa-search mr-2"></i> No rooms found matching your criteria
                        <div class="mt-2 text-sm text-gray-500">
                            Try adjusting your search filters or dates
                        </div>
                    </div>
                `;
                return;
            }

            // Add results summary
            const guests = document.getElementById('guests').value;
            const roomType = document.getElementById('room-type').value;
            const roomTypeText = roomType === 'all' ? 'all types' : document.getElementById('room-type').options[document.getElementById('room-type').selectedIndex].text;
            
            const summary = document.createElement('div');
            summary.className = 'mb-4 p-3 bg-blue-50 rounded-lg';
            summary.innerHTML = `
                <div class="text-sm text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    Found ${rooms.length} room(s) for ${guests} guest(s) - ${roomTypeText}
                </div>
            `;
            searchResults.insertBefore(summary, searchResults.firstChild);

            grid.innerHTML = '';
            rooms.forEach(room => {
                const roomCard = document.createElement('div');
                roomCard.className = 'bg-white rounded-xl overflow-hidden shadow-md room-card transition duration-200 cursor-pointer hover:shadow-lg';
                roomCard.innerHTML = `
                    <div class="relative">
                        <img src="${room.PrimaryImage || 'https://via.placeholder.com/300x200?text=No+Image'}" 
                             alt="Room ${room.RoomNumber}" 
                             class="w-full h-48 object-cover"
                             onclick="viewRoomDetails(${room.RoomID})">
                        <div class="absolute top-2 right-2">
                            <span class="bg-blue-600 text-white px-2 py-1 rounded-full text-xs font-medium">$${room.Price}</span>
                        </div>
                    </div>
                    <div class="p-4" onclick="viewRoomDetails(${room.RoomID})">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-gray-800">Room ${room.RoomNumber}</h3>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${room.TypeName}</span>
                        </div>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">${room.TypeDescription || 'No description available'}</p>
                        <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                            <span><i class="fas fa-users mr-1"></i> ${room.Capacity || 2} guests</span>
                            <span><i class="fas fa-star text-yellow-400 mr-1"></i> 4.5</span>
                        </div>
                        <button onclick="viewRoomDetails(${room.RoomID})" 
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition duration-200">
                            View Details <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                `;
                grid.appendChild(roomCard);
            });
        }
        // View room details for booking
        // Trong file codeweb.php, tìm hàm viewRoomDetails và sửa thành:
        function viewRoomDetails(roomId, checkInDate = null, checkOutDate = null) {

    // Lưu thông tin phòng và ngày đã chọn (nếu có)
    if (checkInDate) selectedDates.checkIn = checkInDate;
    if (checkOutDate) selectedDates.checkOut = checkOutDate;

    // Chuyển đến trang đặt phòng

    showSection('book');

    // Hiển thị loading
    const roomDetailsContainer = document.getElementById('room-details-container');
    roomDetailsContainer.innerHTML = `
        <div class="text-center py-10">
            <i class="fas fa-spinner fa-spin mr-2"></i> Loading room details...
        </div>
    `;

    // Ẩn phần "no room selected" và hiển thị chi tiết phòng
    document.getElementById('no-room-selected').classList.add('hidden');
    roomDetailsContainer.classList.remove('hidden');

    document.getElementById('booking-form-container').classList.add('hidden');

    // Gọi API để lấy thông tin chi tiết phòng

    fetch(`get_room_details.php?id=${roomId}`)
        .then(response => {

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(room => {
            if (room.error) {
                throw new Error(room.error);
            }

            // Set selectedRoom để có thể booking
            selectedRoom = {
                id: room.RoomID,
                name: `Room ${room.RoomNumber}`,
                price: parseFloat(room.Price) || 0,
                type: room.TypeName
            };


            // Tạo lại toàn bộ HTML content cho room details container
            const images = room.images && room.images.length > 0 ? room.images : [];
            const defaultImage = 'https://via.placeholder.com/800x500?text=No+Image';

            roomDetailsContainer.innerHTML = `
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-gray-800" id="book-room-name">Room ${room.RoomNumber}</h2>
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium"
                        id="book-room-type">${room.TypeName}</span>
                </div>

                <!-- Room Images Carousel -->
                <div class="mb-6 relative">
                    <div class="h-64 overflow-hidden rounded-lg relative" id="room-images-carousel">
                        ${images.length > 0 ? `
                            <div class="relative h-full group">
                                <img src="${images[0].ImageURL}" alt="Room" class="w-full h-full object-cover transition-opacity duration-500" id="carousel-main-image" 
                                     onload="this.style.opacity='1'" onerror="this.src='https://via.placeholder.com/800x500?text=Image+Not+Available'">
                                
                                <!-- Navigation arrows -->
                                ${images.length > 1 ? `
                                    <button class="absolute left-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 hover:scale-110 transition-all duration-200 opacity-0 group-hover:opacity-100" 
                                            onclick="changeCarouselImage(-1)" id="carousel-prev-btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </button>
                                    <button class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 hover:scale-110 transition-all duration-200 opacity-0 group-hover:opacity-100" 
                                            onclick="changeCarouselImage(1)" id="carousel-next-btn">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                ` : ''}
                                
                                <!-- Image counter and fullscreen button -->
                                <div class="absolute bottom-2 right-2 flex items-center gap-2">
                                    ${images.length > 1 ? `
                                        <div class="bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
                                            <span id="carousel-counter">1</span> / <span id="carousel-total">${images.length}</span>
                                        </div>
                                    ` : ''}
                                    <button class="bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200 opacity-0 group-hover:opacity-100" 
                                            onclick="toggleFullscreen()" title="Fullscreen">
                                        <i class="fas fa-expand"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Thumbnail navigation -->
                            ${images.length > 1 ? `
                                <div class="flex gap-2 mt-3 overflow-x-auto pb-2 scrollbar-hide" id="carousel-thumbnails">
                                    ${images.map((img, index) => `
                                        <img src="${img.ImageURL}" alt="Thumbnail ${index + 1}" 
                                             class="w-20 h-14 object-cover rounded-lg cursor-pointer transition-all duration-200 border-2 ${index === 0 ? 'border-blue-500 shadow-lg' : 'border-gray-200 opacity-60 hover:opacity-100 hover:border-gray-300'}"
                                             onclick="goToCarouselImage(${index})" data-index="${index}">
                                    `).join('')}
                                </div>
                            ` : ''}
                        ` : `
                            <div class="h-full flex items-center justify-center bg-gray-100 rounded-lg">
                                <img src="${defaultImage}" alt="No Image Available" class="w-full h-full object-cover">
                            </div>
                        `}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-gray-500 text-sm mb-1">Max Guests</div>
                        <div class="font-semibold" id="book-room-guests">2</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-gray-500 text-sm mb-1">Price Per Night</div>
                        <div class="font-semibold" id="book-room-price">$${room.Price || '0'}</div>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2">Amenities</h3>
                    <div class="flex flex-wrap gap-2" id="book-room-amenities">
                    <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">WiFi</span>
                    <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">TV</span>
                    <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">AC</span>
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-2">Description</h3>
                    <p class="text-gray-600" id="book-room-description">${room.TypeDescription || 'No description available'}</p>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i> Select Your Dates
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-gray-700 text-sm font-medium mb-2 block">Check-in Date</label>
                            <input type="date" id="booking-checkin-date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   onchange="updateCheckInDate()">
                        </div>
                        <div>
                            <label class="text-gray-700 text-sm font-medium mb-2 block">Check-out Date</label>
                            <input type="date" id="booking-checkout-date" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   onchange="updateCheckOutDate()">
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-blue-200">
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div class="text-center">
                                <div class="text-gray-500">Nights</div>
                                <div class="font-semibold" id="booking-nights">-</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-500">Price/Night</div>
                                <div class="font-semibold" id="booking-price-per-night">$${room.Price || '0'}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-gray-500">Total</div>
                                <div class="font-bold text-blue-600" id="booking-total-price">$0</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Hiển thị form đặt phòng
            document.getElementById('booking-form-container').classList.remove('hidden');

            // Cập nhật thông tin người dùng nếu đã đăng nhập
            if (currentUser) {
                const bookingNameEl = document.getElementById('booking-name');
                const bookingPhoneEl = document.getElementById('booking-phone');
                const bookingEmailEl = document.getElementById('booking-email');

                if (bookingNameEl) bookingNameEl.value = currentUser.name || '';
                if (bookingPhoneEl) bookingPhoneEl.value = currentUser.phone || '';
                if (bookingEmailEl) bookingEmailEl.value = currentUser.email || '';
            }

            // Cập nhật ngày đặt nếu có (sau khi HTML đã được tạo lại)
            setTimeout(() => {
            const checkInEl = document.getElementById('book-check-in');
            const checkOutEl = document.getElementById('book-check-out');

            if (checkInDate && checkInEl) {
                checkInEl.textContent = new Date(checkInDate).toLocaleDateString();
            }
            if (checkOutDate && checkOutEl) {
                checkOutEl.textContent = new Date(checkOutDate).toLocaleDateString();
                }
            }, 100);

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('booking-checkin-date').min = today;
            document.getElementById('booking-checkout-date').min = today;

            // If dates were already selected, populate them
            if (checkInDate) {
                document.getElementById('booking-checkin-date').value = checkInDate;
                selectedDates.checkIn = checkInDate;
            }
            if (checkOutDate) {
                document.getElementById('booking-checkout-date').value = checkOutDate;
                selectedDates.checkOut = checkOutDate;
            }

            // Initialize carousel
            carouselImages = images;
            currentCarouselIndex = 0;
            
            // Start auto-play if there are multiple images
            if (images.length > 1) {
                setTimeout(() => {
                    startCarouselAutoPlay();
                }, 1000); // Start auto-play after 1 second
            }
            
            // Calculate initial price if both dates are set
            if (checkInDate && checkOutDate) {
                calculateBookingPrice();
            }
        })
        .catch(error => {
            console.error('Error loading room details:', error);
            roomDetailsContainer.innerHTML = `
                <div class="text-center py-10 text-red-500">
                    <i class="fas fa-exclamation-triangle mr-2"></i> 
                    Error loading room details: ${error.message}
                </div>
            `;
        });
}

        // Carousel functions for room images
        let currentCarouselIndex = 0;
        let carouselImages = [];
        let carouselAutoPlayInterval = null;

        function changeCarouselImage(direction) {
            if (carouselImages.length <= 1) return;
            
            // Stop auto-play when user interacts
            stopCarouselAutoPlay();
            
            currentCarouselIndex += direction;
            
            if (currentCarouselIndex >= carouselImages.length) {
                currentCarouselIndex = 0;
            } else if (currentCarouselIndex < 0) {
                currentCarouselIndex = carouselImages.length - 1;
            }
            
            updateCarouselDisplay();
        }

        function goToCarouselImage(index) {
            if (index >= 0 && index < carouselImages.length) {
                // Stop auto-play when user interacts
                stopCarouselAutoPlay();
                
                currentCarouselIndex = index;
                updateCarouselDisplay();
            }
        }

        function updateCarouselDisplay() {
            const mainImage = document.getElementById('carousel-main-image');
            const counter = document.getElementById('carousel-counter');
            const thumbnails = document.querySelectorAll('#carousel-thumbnails img');
            
            if (mainImage && carouselImages[currentCarouselIndex]) {
                mainImage.style.opacity = '0';
                mainImage.src = carouselImages[currentCarouselIndex].ImageURL;
                
                // Add loading effect
                mainImage.onload = function() {
                    this.style.opacity = '1';
                };
                mainImage.onerror = function() {
                    this.src = 'https://via.placeholder.com/800x500?text=Image+Not+Available';
                    this.style.opacity = '1';
                };
            }
            
            if (counter) {
                counter.textContent = currentCarouselIndex + 1;
            }
            
            // Update thumbnail selection
            thumbnails.forEach((thumb, index) => {
                if (index === currentCarouselIndex) {
                    thumb.classList.add('border-blue-500', 'shadow-lg');
                    thumb.classList.remove('border-gray-200', 'opacity-60');
                } else {
                    thumb.classList.remove('border-blue-500', 'shadow-lg');
                    thumb.classList.add('border-gray-200', 'opacity-60');
                }
            });
        }

        function startCarouselAutoPlay() {
            if (carouselImages.length <= 1) return;
            
            stopCarouselAutoPlay();
            carouselAutoPlayInterval = setInterval(() => {
                changeCarouselImage(1);
            }, 3000); // Change image every 3 seconds
        }

        function stopCarouselAutoPlay() {
            if (carouselAutoPlayInterval) {
                clearInterval(carouselAutoPlayInterval);
                carouselAutoPlayInterval = null;
            }
        }

        // Keyboard navigation for carousel
        document.addEventListener('keydown', function(e) {
            const carousel = document.getElementById('room-images-carousel');
            if (carousel && !carousel.classList.contains('hidden')) {
                if (e.key === 'ArrowLeft') {
                    e.preventDefault();
                    changeCarouselImage(-1);
                } else if (e.key === 'ArrowRight') {
                    e.preventDefault();
                    changeCarouselImage(1);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    exitFullscreen();
                }
            }
        });

        // Fullscreen functionality
        function toggleFullscreen() {
            const carousel = document.getElementById('room-images-carousel');
            if (!carousel) return;
            
            if (!document.fullscreenElement) {
                carousel.requestFullscreen().catch(err => {
                    console.log('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        function exitFullscreen() {
            if (document.fullscreenElement) {
                document.exitFullscreen();
            }
        }

        // Update check-in date
        function updateCheckInDate() {
            const checkInInput = document.getElementById('booking-checkin-date');
            const checkOutInput = document.getElementById('booking-checkout-date');
            
            selectedDates.checkIn = checkInInput.value;
            
            // Set minimum checkout date to day after check-in
            if (checkInInput.value) {
                const checkInDate = new Date(checkInInput.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOut = checkInDate.toISOString().split('T')[0];
                checkOutInput.min = minCheckOut;
                
                // If checkout is before new minimum, clear it
                if (checkOutInput.value && checkOutInput.value <= checkInInput.value) {
                    checkOutInput.value = '';
                    selectedDates.checkOut = null;
                }
            }
            
            calculateBookingPrice();
        }

        // Update check-out date
        function updateCheckOutDate() {
            const checkOutInput = document.getElementById('booking-checkout-date');
            selectedDates.checkOut = checkOutInput.value;
            calculateBookingPrice();
        }

        // Calculate booking price
        function calculateBookingPrice() {
            if (!selectedDates.checkIn || !selectedDates.checkOut || !selectedRoom) {
                document.getElementById('booking-nights').textContent = '-';
                document.getElementById('booking-total-price').textContent = '$0';
                return;
            }

            const checkIn = new Date(selectedDates.checkIn);
            const checkOut = new Date(selectedDates.checkOut);
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            
            if (nights > 0) {
                const totalPrice = nights * selectedRoom.price;
                document.getElementById('booking-nights').textContent = nights;
                document.getElementById('booking-total-price').textContent = `$${totalPrice.toFixed(2)}`;
            } else {
                document.getElementById('booking-nights').textContent = '-';
                document.getElementById('booking-total-price').textContent = '$0';
            }
}
        // Update booking summary with dates and prices
        function updateBookingSummary() {
            if (!selectedRoom) return;

            if (selectedDates.checkIn) {
                document.getElementById('book-check-in').textContent = formatDate(selectedDates.checkIn);
            }

            if (selectedDates.checkOut) {
                document.getElementById('book-check-out').textContent = formatDate(selectedDates.checkOut);
            }

            if (selectedDates.checkIn && selectedDates.checkOut) {
                const checkIn = new Date(selectedDates.checkIn);
                const checkOut = new Date(selectedDates.checkOut);
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const totalPrice = nights * selectedRoom.price;

                document.getElementById('book-total-nights').textContent = `${nights} night${nights !== 1 ? 's' : ''}`;
                document.getElementById('book-total-price').textContent = `$${totalPrice}`;
            }
        }

        // Format date for display
        function formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            return new Date(dateString).toLocaleDateString('en-US', options);
        }

        // Handle login form submission
        // Handle login form submission
        function handleLogin(event) {
            event.preventDefault();

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            if (!email || !password) {
                showErrorModal('Login Error', 'Please enter both email and password.');
                return;
            }

            // Show loading indicator
            const loginBtn = document.querySelector('#login-form button[type="submit"]');
            const originalBtnText = loginBtn.innerHTML;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            loginBtn.disabled = true;

            // Send data to server
            fetch('login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store user data
                        currentUser = data.user;
                        localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));
                        updateUIForUser();

                        showSuccessModal('Login Successful', 'Welcome back, ' + data.user.name + '!');
                        showSection('home');
                    } else {
                        showErrorModal('Login Error', data.message || 'Invalid email or password');
                    }
                })
                .catch(error => {
                    showErrorModal('Login Error', 'An error occurred. Please try again.');
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Restore button state
                    loginBtn.innerHTML = originalBtnText;
                    loginBtn.disabled = false;
                });
        }

        // Add event listener for login form
        document.getElementById('login-form').addEventListener('submit', handleLogin);

        // Enhanced registration validation functions
        function validateEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validatePhone(phone) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            return phoneRegex.test(phone.replace(/\s/g, ''));
        }

        function validateName(name) {
            const nameRegex = /^[a-zA-Z\s]{2,50}$/;
            return nameRegex.test(name.trim());
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            if (requirements.length) strength += 20;
            if (requirements.uppercase) strength += 20;
            if (requirements.lowercase) strength += 20;
            if (requirements.number) strength += 20;
            if (requirements.special) strength += 20;

            return { strength, requirements };
        }

        function updatePasswordStrength(password) {
            const { strength, requirements } = checkPasswordStrength(password);
            const bar = document.getElementById('password-strength-bar');
            const text = document.getElementById('password-strength-text');
            const reqs = document.getElementById('password-requirements');

            // Update strength bar
            bar.style.width = strength + '%';
            if (strength < 40) {
                bar.className = 'bg-red-500 h-1 rounded-full transition-all duration-300';
                text.textContent = 'Weak';
                text.className = 'text-red-500';
            } else if (strength < 80) {
                bar.className = 'bg-yellow-500 h-1 rounded-full transition-all duration-300';
                text.textContent = 'Medium';
                text.className = 'text-yellow-500';
            } else {
                bar.className = 'bg-green-500 h-1 rounded-full transition-all duration-300';
                text.textContent = 'Strong';
                text.className = 'text-green-500';
            }

            // Update requirements
            const reqItems = reqs.querySelectorAll('div');
            reqItems[0].innerHTML = `<i class="fas fa-${requirements.length ? 'check' : 'circle'} ${requirements.length ? 'text-green-500' : 'text-gray-300'} mr-1"></i> At least 8 characters`;
            reqItems[1].innerHTML = `<i class="fas fa-${requirements.uppercase ? 'check' : 'circle'} ${requirements.uppercase ? 'text-green-500' : 'text-gray-300'} mr-1"></i> One uppercase letter`;
            reqItems[2].innerHTML = `<i class="fas fa-${requirements.lowercase ? 'check' : 'circle'} ${requirements.lowercase ? 'text-green-500' : 'text-gray-300'} mr-1"></i> One lowercase letter`;
            reqItems[3].innerHTML = `<i class="fas fa-${requirements.number ? 'check' : 'circle'} ${requirements.number ? 'text-green-500' : 'text-gray-300'} mr-1"></i> One number`;
        }

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');

            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            const indicator = document.getElementById('password-match-indicator');

            if (confirmPassword && password === confirmPassword) {
                indicator.classList.remove('hidden');
            } else {
                indicator.classList.add('hidden');
            }
        }

        // Handle registration form submission
        function handleRegister(event) {
            event.preventDefault();

            const name = document.getElementById('register-name').value.trim();
            const email = document.getElementById('register-email').value.trim();
            const phone = document.getElementById('register-phone').value.trim();
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            const terms = document.getElementById('register-terms').checked;

            // Validate form
            if (!name || !email || !phone || !password || !confirmPassword) {
                showErrorModal('Registration Error', 'Please fill in all fields.');
                return;
            }

            if (!validateEmail(email)) {
                showErrorModal('Registration Error', 'Please enter a valid email address.');
                return;
            }

            if (password !== confirmPassword) {
                showErrorModal('Registration Error', 'Passwords do not match.');
                return;
            }

            if (!terms) {
                showErrorModal('Registration Error', 'Please agree to the Terms of Service and Privacy Policy.');
                return;
            }

            // Tạo đối tượng dữ liệu để gửi
            const formData = {
                name: name,
                email: email,
                phone: phone,
                password: password // Không cần hash ở phía client
            };

            // Gửi dữ liệu đến register.php
            fetch('register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal('Registration Successful', data.message || 'Your account has been created successfully!');
                        // Reset form
                        document.getElementById('register-form').reset();
                        // Chuyển đến trang login sau 2 giây
                        setTimeout(() => showSection('login'), 2000);
                    } else {
                        showErrorModal('Registration Error', data.message || 'Registration failed. Please try again.');
                    }
                })
                .catch(error => {
                    showErrorModal('Registration Error', 'An error occurred. Please try again.');
                    console.error('Error:', error);
                });
        }

        // Handle search form submission
        function handleSearch(event) {
            event.preventDefault();

            const checkInDate = document.getElementById('check-in-date').value;
            const checkOutDate = document.getElementById('check-out-date').value;
            const guests = document.getElementById('guests').value;
            const roomType = document.getElementById('room-type').value;

            // Validation
            if (!checkInDate || !checkOutDate) {
                showErrorModal('Search Error', 'Please select both check-in and check-out dates.');
                return;
            }

            // In a real app, this would be an API call to check availability
            // For demo, we'll just filter from our sample data

            let availableRooms = rooms.filter(room => {
                // Filter by max guests
                if (room.maxGuests < guests) return false;

                // Filter by room type if not "all"
                if (roomType !== 'all' && room.type !== roomType) return false;

                return true;
            });

            // Display results
            displaySearchResults(availableRooms, checkInDate, checkOutDate);
        }



        // Handle booking form submission
        function handleBooking(event) {
            event.preventDefault();

            if (!currentUser) {
                showErrorModal('Booking Error', 'You need to login to make a booking.');
                showSection('login');
                return;
            }

            if (!selectedRoom) {
                showErrorModal('Booking Error', 'Please select a room first.');
                return;
            }

            if (!selectedDates.checkIn || !selectedDates.checkOut) {
                showErrorModal('Booking Error', 'Please select both check-in and check-out dates.');
                return;
            }

            const name = document.getElementById('booking-name').value;
            const phone = document.getElementById('booking-phone').value;
            const specialRequests = document.getElementById('booking-special-requests').value;
            const paymentMethod = document.getElementById('booking-payment-method').value;

            // Validate
            if (!name || !phone) {
                showErrorModal('Booking Error', 'Please fill in all required fields.');
                return;
            }

            // Calculate total nights and price
            const checkIn = new Date(selectedDates.checkIn);
            const checkOut = new Date(selectedDates.checkOut);
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            const totalPrice = nights * selectedRoom.price;

            // Prepare booking data
            const bookingData = {
                roomID: selectedRoom.id,
                checkinDate: selectedDates.checkIn + ' 14:00:00', // Default check-in time 2PM
                checkoutDate: selectedDates.checkOut + ' 11:00:00', // Default check-out time 11AM
                totalAmount: totalPrice,
                specialRequests: specialRequests,
                paymentMethod: paymentMethod
            };

            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;

            // Send booking request to server
            fetch('process_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(bookingData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Booking successful
                        const bookingId = data.bookingID;

                        // Update UI
                        showSuccessModal('Booking Confirmed',
                            `Your booking for ${selectedRoom.name} has been confirmed. 
                Your booking ID is ${bookingId}. 
                An email confirmation has been sent to ${currentUser.email}.`);

                        // Reset form
                        document.getElementById('booking-form').reset();
                        selectedRoom = null;
                        selectedDates = { checkIn: null, checkOut: null };
                        document.getElementById('no-room-selected').classList.remove('hidden');
                        document.getElementById('room-details-container').classList.add('hidden');
                        document.getElementById('booking-form-container').classList.add('hidden');

                        // Refresh booking history if on that page
                        if (document.getElementById('history-section') &&
                            !document.getElementById('history-section').classList.contains('hidden-section')) {
                            displayBookingHistory();
                        }
                    } else {
                        showErrorModal('Booking Error', data.message || 'Booking failed. Please try again.');
                    }
                })
                .catch(error => {
                    showErrorModal('Booking Error', 'An error occurred. Please try again.');
                    console.error('Error:', error);
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
        }

        function finishBooking(name, phone, specialRequests, checkIn, checkOut, nights, totalPrice, paymentStatus) {
            const bookingId = `RES-${new Date().getFullYear()}-${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}`;
            const newBooking = {
                id: bookingId,
                roomId: selectedRoom.id,
                roomName: selectedRoom.name,
                checkIn: checkIn.toISOString(),
                checkOut: checkOut.toISOString(),
                status: 'confirmed',
                guestName: name,
                totalPrice: totalPrice,
                specialRequests: specialRequests,
                paymentStatus: paymentStatus
            };
            bookings.unshift(newBooking);
            showSuccessModal('Booking Confirmed', `Your booking for ${selectedRoom.name} has been confirmed. Your booking ID is ${bookingId}. An email confirmation has been sent to ${currentUser.email}.`);
            document.getElementById('booking-form').reset();
            selectedRoom = null;
            selectedDates = { checkIn: null, checkOut: null };
            document.getElementById('no-room-selected').classList.remove('hidden');
            document.getElementById('room-details-container').classList.add('hidden');
            document.getElementById('booking-form-container').classList.add('hidden');
            if (document.getElementById('history-section') && !document.getElementById('history-section').classList.contains('hidden-section')) {
                displayBookingHistory();
            }
        }

        // Display booking history
        function displayBookingHistory() {
            if (!currentUser) {
                document.getElementById('booking-history-body').innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Login to view your booking history</td>
            </tr>
        `;
                return;
            }

            const tbody = document.getElementById('booking-history-body');

            // Show loading state
            tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading your bookings...
            </td>
        </tr>
    `;

            // Fetch user bookings from API
            fetch(`user_get_bookings.php?userID=${currentUser.id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const bookings = data.bookings;

                        tbody.innerHTML = bookings.length ? '' : `
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings found</td>
                    </tr>
                `;

                        bookings.forEach(booking => {
                            const checkInDate = new Date(booking.CheckinDate);
                            const checkOutDate = new Date(booking.CheckoutDate);

                            let statusBadge = '';
                            switch (booking.Status.toLowerCase()) {
                                case 'confirmed':
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>';
                                    break;
                                case 'cancelled':
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>';
                                    break;
                                case 'pending':
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                                    break;
                                case 'checked-in':
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Checked-in</span>';
                                    break;
                                case 'completed':
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Completed</span>';
                                    break;
                                default:
                                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">N/A</span>';
                            }

                            // Create action buttons based on booking status
                            let actionButtons = '';
                            
                            // Cancel button for pending/confirmed bookings
                            if (booking.Status.toLowerCase() === 'pending' || booking.Status.toLowerCase() === 'confirmed') {
                                actionButtons += `
                                    <button onclick="userCancelBooking('${booking.BookingID}')" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs mr-1">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </button>
                                `;
                            }
                            
                            // Checkout request button for checked-in bookings
                            if (booking.Status.toLowerCase() === 'checked-in') {
                                actionButtons += `
                                    <button onclick="userRequestCheckout('${booking.BookingID}')" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs mr-1">
                                        <i class="fas fa-sign-out-alt mr-1"></i> Request Checkout
                                    </button>
                                `;
                            }
                            
                            // Print button for all bookings
                            actionButtons += `
                                <button onclick="printBooking('${booking.BookingID}')" 
                                    class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs">
                                    <i class="fas fa-print"></i>
                                </button>
                            `;

                            tbody.innerHTML += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${booking.BookingID}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.RoomType} #${booking.RoomNumber}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${booking.TotalAmount}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex flex-wrap gap-1">
                                    ${actionButtons}
                                </div>
                        </td>
                        </tr>
                    `;
                        });
                    } else {
                        throw new Error(data.message || 'Failed to load bookings');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Error loading bookings: ${error.message}
                    </td>
                </tr>
            `;
                });
        }

        // Cancel a booking
        function cancelBooking(bookingID) {
            if (!confirm('Are you sure you want to cancel this booking?')) return;

            // Show loading state
            const btn = event.target;
            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            // Send cancel request
            fetch('cancel_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ bookingID: bookingID })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal('Booking Cancelled', `Your booking ${bookingID} has been cancelled successfully.`);
                        displayBookingHistory();
                    } else {
                        throw new Error(data.message || 'Failed to cancel booking');
                    }
                })
                .catch(error => {
                    showErrorModal('Cancellation Error', error.message || 'An error occurred while cancelling booking');
                })
                .finally(() => {
                    btn.innerHTML = originalBtnText;
                    btn.disabled = false;
                });
        }

        // Print booking (simulated)
        function printBooking(bookingId) {
            // In a real app, this would open a print dialog with booking details
            alert(`Print functionality would show details for booking ${bookingId}`);
        }

        // Load profile data from server
        function loadProfileData() {
            if (!currentUser) return;
            
            fetch('get_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        // Update form fields
                        document.getElementById('profile-name').value = user.Fullname;
                        document.getElementById('profile-email').value = user.Email;
                        document.getElementById('profile-phone').value = user.Phonenumber;
                        
                        // Update display info
                        document.getElementById('profile-display-name').textContent = user.Fullname;
                        document.getElementById('profile-display-email').textContent = user.Email;
                        
                        // Update avatar
                        const avatarImg = document.getElementById('profile-avatar');
                        if (user.Avatar && user.Avatar.startsWith('data:image')) {
                            // Base64 avatar
                            avatarImg.src = user.Avatar;
                        } else if (user.Avatar && user.Avatar.startsWith('uploads/')) {
                            // File path avatar (legacy)
                            avatarImg.src = user.Avatar;
                        } else {
                            // Default avatar
                            avatarImg.src = 'https://source.unsplash.com/random/300x300/?portrait';
                        }
                        
                        // Update additional info if available
                        const additionalInfo = document.getElementById('profile-additional-info');
                        if (additionalInfo) {
                            additionalInfo.innerHTML = `
                                <div class="text-sm text-gray-600">
                                    <p><strong>Username:</strong> ${user.Username}</p>
                                    <p><strong>Role:</strong> ${user.RoleName}</p>
                                    <p><strong>Member since:</strong> ${user.CreatedAt}</p>
                                    <p><strong>Last updated:</strong> ${user.UpdatedAt}</p>
                                </div>
                            `;
                        }
                        
                        // Update current user data with latest info
                        currentUser.name = user.Fullname;
                        currentUser.avatar = user.Avatar;
                        localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));
                        
                        // Update navigation avatar and username
                        updateNavigationAvatar();
                    } else {
                        console.error('Failed to load profile:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading profile:', error);
                });
        }

        // Remove avatar function
        function removeAvatar() {
            if (!currentUser) {
                showErrorModal('Remove Error', 'You need to login to remove avatar.');
                return;
            }

            if (!confirm('Are you sure you want to remove your avatar?')) {
                return;
            }

            fetch('remove_avatar.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update avatar image to default
                    const avatarImg = document.getElementById('profile-avatar');
                    avatarImg.src = 'https://source.unsplash.com/random/300x300/?portrait';
                    
                    // Update current user data
                    currentUser.avatar = null;
                    localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));
                    
                    // Update navigation avatar
                    updateNavigationAvatar();
                    
                    showSuccessModal('Avatar Removed', data.message);
                } else {
                    showErrorModal('Remove Error', data.message);
                }
            })
            .catch(error => {
                console.error('Remove avatar error:', error);
                showErrorModal('Remove Error', 'Failed to remove avatar. Please try again.');
            });
        }

        // Upload avatar function
        function uploadAvatar(input) {
            if (!currentUser) {
                showErrorModal('Upload Error', 'You need to login to upload avatar.');
                return;
            }

            const file = input.files[0];
            if (!file) return;

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                showErrorModal('Upload Error', 'Only JPG, PNG, and GIF files are allowed.');
                return;
            }

            // Validate file size (max 5MB)
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                showErrorModal('Upload Error', 'File size must be less than 5MB.');
                return;
            }

            // Show loading state
            const avatarImg = document.getElementById('profile-avatar');
            const originalSrc = avatarImg.src;
            avatarImg.style.opacity = '0.5';

            // Create FormData
            const formData = new FormData();
            formData.append('avatar', file);

            // Upload file
            fetch('upload_avatar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Debug: Log avatar data
                    console.log('Avatar uploaded successfully');
                    console.log('Avatar URL length:', data.avatar_url.length);
                    console.log('Avatar URL starts with data:image:', data.avatar_url.startsWith('data:image'));
                    
                    // Update avatar image
                    avatarImg.src = data.avatar_url;
                    avatarImg.style.opacity = '1';
                    
                    // Update current user data
                    currentUser.avatar = data.avatar_url;
                    localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));
                    
                    // Update navigation avatar
                    updateNavigationAvatar();
                    
                    showSuccessModal('Avatar Updated', data.message);
                } else {
                    showErrorModal('Upload Error', data.message);
                    avatarImg.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Upload error:', error);
                showErrorModal('Upload Error', 'Failed to upload avatar. Please try again.');
                avatarImg.style.opacity = '1';
            })
            .finally(() => {
                // Reset file input
                input.value = '';
            });
        }

        // Handle profile update
        function handleProfileUpdate(event) {
            event.preventDefault();

            if (!currentUser) {
                showErrorModal('Profile Error', 'You need to login to update your profile.');
                showSection('login');
                return;
            }

            const name = document.getElementById('profile-name').value;
            const phone = document.getElementById('profile-phone').value;
            const currentPassword = document.getElementById('profile-current-password').value;
            const newPassword = document.getElementById('profile-new-password').value;
            const confirmPassword = document.getElementById('profile-confirm-password').value;

            // Validate name and phone
            if (!name || !phone) {
                showErrorModal('Profile Error', 'Please fill in all required fields.');
                return;
            }

            // If changing password, validate passwords
            if (currentPassword || newPassword || confirmPassword) {
                if (!currentPassword || !newPassword || !confirmPassword) {
                    showErrorModal('Profile Error', 'Please fill in all password fields to change your password.');
                    return;
                }

                if (newPassword !== confirmPassword) {
                    showErrorModal('Profile Error', 'New passwords do not match.');
                    return;
                }

                if (newPassword.length < 6) {
                    showErrorModal('Profile Error', 'New password must be at least 6 characters long.');
                    return;
                }
            }

            // Prepare data for API
            const profileData = {
                name: name,
                phone: phone
            };

            // Add password data if changing password
            if (currentPassword && newPassword) {
                profileData.current_password = currentPassword;
                profileData.new_password = newPassword;
            }

            // Show loading state
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Updating...';
            submitBtn.disabled = true;

            // Call API to update profile
            fetch('update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(profileData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update current user data
                    currentUser.name = data.user.name;
                    currentUser.phone = data.user.phone;
                    
                    // Update localStorage
                    localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));
                    
                    // Update displayed profile info
                    document.getElementById('profile-display-name').textContent = data.user.name;
                    document.getElementById('profile-display-email').textContent = data.user.email;
                    
                    // Update navigation username
                    updateNavigationAvatar();
                    
                    showSuccessModal('Profile Updated', data.message);
                    
                    // Reset password fields
                    document.getElementById('profile-current-password').value = '';
                    document.getElementById('profile-new-password').value = '';
                    document.getElementById('profile-confirm-password').value = '';
                } else {
                    showErrorModal('Profile Error', data.message);
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                showErrorModal('Profile Error', 'Failed to update profile. Please try again.');
            })
            .finally(() => {
                // Reset button state
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        // Show success modal
        function showSuccessModal(title, message) {
            document.getElementById('success-title').textContent = title;
            document.getElementById('success-message').textContent = message;
            document.getElementById('success-modal').classList.remove('hidden');
        }

        // Hide success modal
        function hideSuccessModal() {
            document.getElementById('success-modal').classList.add('hidden');
        }

        // Show error modal (simplified for this demo - would be similar to success modal)
        function showErrorModal(title, message) {
            alert(`${title}: ${message}`);
        }

        // Update navigation avatar and username
        function updateNavigationAvatar() {
            const navAvatar = document.getElementById('nav-avatar');
            const navAvatarMobile = document.getElementById('nav-avatar-mobile');
            const navUsername = document.getElementById('nav-username');
            const navUsernameMobile = document.getElementById('nav-username-mobile');
            
            if (currentUser) {
                // Set avatar with better handling
                if (currentUser.avatar && currentUser.avatar.startsWith('data:image')) {
                    // Base64 avatar - ensure proper display
                    navAvatar.src = currentUser.avatar;
                    navAvatarMobile.src = currentUser.avatar;
                    
                    // Add error handling for base64 images
                    navAvatar.onerror = function() {
                        this.src = 'https://source.unsplash.com/random/32x32/?portrait';
                    };
                    navAvatarMobile.onerror = function() {
                        this.src = 'https://source.unsplash.com/random/32x32/?portrait';
                    };
                } else if (currentUser.avatar && currentUser.avatar.startsWith('uploads/')) {
                    // File path avatar (legacy)
                    navAvatar.src = currentUser.avatar;
                    navAvatarMobile.src = currentUser.avatar;
                } else {
                    // Default avatar
                    navAvatar.src = 'https://source.unsplash.com/random/32x32/?portrait';
                    navAvatarMobile.src = 'https://source.unsplash.com/random/32x32/?portrait';
                }
                
                // Set username
                const displayName = currentUser.name || currentUser.username || 'User';
                navUsername.textContent = displayName;
                navUsernameMobile.textContent = displayName;
            } else {
                // Reset to default
                navAvatar.src = 'https://source.unsplash.com/random/32x32/?portrait';
                navAvatarMobile.src = 'https://source.unsplash.com/random/32x32/?portrait';
                navUsername.textContent = 'Profile';
                navUsernameMobile.textContent = 'Profile';
            }
        }

        // Update UI based on user authentication state
        function updateUIForUser() {
            const authLinks = document.getElementById('auth-links');
            const authLinksMobile = document.getElementById('auth-links-mobile');
            const userLinks = document.getElementById('user-links');
            const userLinksMobile = document.getElementById('user-links-mobile');
            const adminLinks = document.getElementById('admin-links');
            const adminLinksMobile = document.getElementById('admin-links-mobile');
            const receptionLinks = document.getElementById('reception-links');
            const receptionLinksMobile = document.getElementById('reception-links-mobile');

            if (currentUser) {
                // Hide auth links
                authLinks.classList.add('hidden');
                authLinksMobile.classList.add('hidden');

                // Show user links
                userLinks.classList.remove('hidden');
                userLinksMobile.classList.remove('hidden');

                // Show admin/reception links based on role
                if (currentUser.role === 'Admin') {
                    adminLinks.classList.remove('hidden');
                    adminLinksMobile.classList.remove('hidden');
                } else {
                    adminLinks.classList.add('hidden');
                    adminLinksMobile.classList.add('hidden');
                }

                if (currentUser.role === 'Reception') {
                    receptionLinks.classList.remove('hidden');
                    receptionLinksMobile.classList.remove('hidden');
                } else {
                    receptionLinks.classList.add('hidden');
                    receptionLinksMobile.classList.add('hidden');
                }

                // Update profile info if on profile page
                if (document.getElementById('profile-section') && !document.getElementById('profile-section').classList.contains('hidden-section')) {
                    loadProfileData();
                }
                
                // Update navigation avatar and username
                updateNavigationAvatar();
            } else {
                // Show auth links
                authLinks.classList.remove('hidden');
                authLinksMobile.classList.remove('hidden');

                // Hide user links
                userLinks.classList.add('hidden');
                userLinksMobile.classList.add('hidden');
                adminLinks.classList.add('hidden');
                adminLinksMobile.classList.add('hidden');
                receptionLinks.classList.add('hidden');
                receptionLinksMobile.classList.add('hidden');
            }
        }

        // Logout function
        function logout() {
            currentUser = null;
            localStorage.removeItem('hotelBookingUser');
            updateUIForUser();
            showSection('home');

            // Reset selected room and dates
            selectedRoom = null;
            selectedDates = { checkIn: null, checkOut: null };

            // Hide booking form if open
            document.getElementById('no-room-selected').classList.remove('hidden');
            document.getElementById('room-details-container').classList.add('hidden');
            document.getElementById('booking-form-container').classList.add('hidden');
        }

        function sendCleanupRequest(roomId) {
            showSuccessModal('Cleanup Request Sent', `Room ${roomId}: Cleanup request has been sent to housekeeping (demo only).`);
        }

        // Toggle payment form based on selected method
        function togglePaymentForm() {
            const method = document.getElementById('booking-payment-method').value;

            // Hide all payment forms
            document.getElementById('stripe-payment-form').classList.add('hidden');
            document.getElementById('paypal-payment-form').classList.add('hidden');
            document.getElementById('bank-transfer-form').classList.add('hidden');

            // Show selected payment form
            if (method === 'stripe') {
                document.getElementById('stripe-payment-form').classList.remove('hidden');
            } else if (method === 'paypal') {
                document.getElementById('paypal-payment-form').classList.remove('hidden');
            } else if (method === 'bank_transfer') {
                document.getElementById('bank-transfer-form').classList.remove('hidden');
            }
        }

        // Admin user management
        // Admin user management
        function renderAdminUsers(filter = '') {
            const tbody = document.getElementById('admin-user-table-body');

            // Show loading state
            tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading users...
            </td>
        </tr>
    `;

            // Build URL with filter parameter if needed
            let url = 'admin_get_users.php';
            if (filter) {
                url += `?search=${encodeURIComponent(filter)}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(users => {
                    if (users.error) throw new Error(users.error);

                    if (filter) {
                        users = users.filter(u =>
                            u.name.toLowerCase().includes(filter.toLowerCase()) ||
                            u.email.toLowerCase().includes(filter.toLowerCase())
                        );
                    }

                    tbody.innerHTML = users.length ? '' : `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        No users found
                    </td>
                </tr>
            `;

                    users.forEach(user => {
                        tbody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.id}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.name}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.role === 'Admin' ? 'bg-purple-100 text-purple-800' : user.role === 'Reception' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">
                                ${user.role}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="showUserModal('edit', ${user.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-red-600 hover:text-red-900" onclick="deleteUser(${user.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Error loading users: ${error.message}
                    </td>
                </tr>
            `;
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // ...existing code...
            renderAdminUsers();
            document.querySelector('#admin-users-tab input[type="text"]').addEventListener('input', function () {
                renderAdminUsers(this.value);
            });
            // Thêm sự kiện submit cho form
            document.getElementById('user-modal-form').addEventListener('submit', function (e) {
                e.preventDefault();

                const id = document.getElementById('user-modal-id').value;
                const name = document.getElementById('user-modal-name').value;
                const email = document.getElementById('user-modal-email').value;
                const password = document.getElementById('user-modal-password').value;
                const role = document.getElementById('user-modal-role').value;
                const status = document.getElementById('user-modal-status').value;

                // Validate
                if (!name || !email || (!id && !password)) {
                    showErrorModal('Validation Error', 'Please fill in all required fields');
                    return;
                }

                // Prepare data
                const userData = {
                    id: id || null,
                    name: name,
                    email: email,
                    password: password,
                    role: role,
                    status: status
                };

                // Determine if this is an add or edit operation
                const isEdit = !!id;

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                submitBtn.disabled = true;

                // Send request
                fetch(isEdit ? 'admin_update_user.php' : 'admin_add_user.php', {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(userData)
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            hideUserModal();
                            renderAdminUsers();
                            showSuccessModal('Success', data.message || (isEdit ? 'User updated successfully' : 'User added successfully'));
                        } else {
                            throw new Error(data.message || 'Operation failed');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorModal('Error', error.message || 'An error occurred');
                    })
                    .finally(() => {
                        submitBtn.innerHTML = originalBtnText;
                        submitBtn.disabled = false;
                    });
            });

            // Khởi tạo danh sách người dùng khi vào trang admin
            if (window.location.href.includes('section=admin')) {
                renderAdminUsers();
            }
        });

        function showUserModal(mode, id = null) {
            const modal = document.getElementById('user-modal');
            const title = document.getElementById('user-modal-title');
            const form = document.getElementById('user-modal-form');

            modal.classList.remove('hidden');
            title.textContent = mode === 'add' ? 'Add User' : 'Edit User';
            form.reset();
            document.getElementById('user-modal-id').value = id || '';

            if (mode === 'edit' && id) {
                // Fetch user data
                fetch(`admin_get_user.php?id=${id}`)
                    .then(response => response.json())
                    .then(user => {
                        if (user.error) throw new Error(user.error);

                        document.getElementById('user-modal-name').value = user.name || '';
                        document.getElementById('user-modal-email').value = user.email || '';
                        document.getElementById('user-modal-role').value = user.role.toLowerCase() || 'user';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showErrorModal('Error', 'Failed to load user data');
                        hideUserModal();
                    });
            }
        }

        function saveUser() {
            const id = document.getElementById('user-modal-id').value;
            const name = document.getElementById('user-modal-name').value;
            const email = document.getElementById('user-modal-email').value;
            const password = document.getElementById('user-modal-password').value;
            const role = document.getElementById('user-modal-role').value;

            if (!name || !email || (!id && !password)) {
                showErrorModal('Validation Error', 'Please fill in all required fields');
                return;
            }

            const userData = {
                id: id || null,
                name: name,
                email: email,
                password: password,
                role: role
            };

            const url = id ? 'admin_update_user.php' : 'admin_add_user.php';
            const method = id ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        hideUserModal();
                        renderAdminUsers();
                        showSuccessModal('Success', data.message || 'User saved successfully');
                    } else {
                        showErrorModal('Error', data.message || 'Failed to save user');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Error', 'An error occurred while saving user');
                });
        }

        function hideUserModal() {
            document.getElementById('user-modal').classList.add('hidden');
        }
        function deleteUser(id) {
            if (!confirm('Are you sure you want to delete this user?')) return;

            fetch('admin_delete_user.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminUsers();
                        showSuccessModal('Success', data.message || 'User deleted successfully');
                    } else {
                        showErrorModal('Error', data.message || 'Failed to delete user');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Error', 'An error occurred while deleting user');
                });
        }

        // Thêm sự kiện submit cho form
        document.getElementById('user-modal-form').addEventListener('submit', function (e) {
            e.preventDefault();
            saveUser();
        });

        // Room management functions
        function renderAdminRooms(filter = '') {
            const tbody = document.getElementById('admin-room-table-body');

            // Show loading state
            tbody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading rooms...
            </td>
        </tr>
    `;

            // Build URL with filter parameter if needed
            let url = 'admin_get_rooms.php';
            if (filter) {
                url += `?search=${encodeURIComponent(filter)}`;
            }

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(rooms => {
                    if (rooms.error) throw new Error(rooms.error);

                    if (filter) {
                        rooms = rooms.filter(r =>
                            r.RoomNumber.toLowerCase().includes(filter.toLowerCase()) ||
                            r.TypeName.toLowerCase().includes(filter.toLowerCase())
                        );
                    }

                    tbody.innerHTML = rooms.length ? '' : `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        No rooms found
                    </td>
                </tr>
            `;

                    rooms.forEach(room => {
                        tbody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.RoomID}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            ${room.PrimaryImage ?
                                `<img src="${room.PrimaryImage}" alt="Room ${room.RoomNumber}" style="width:60px; height:40px; object-fit:cover; border-radius:6px;">` :
                                `<i class="fas fa-image text-gray-300" style="font-size:40px;"></i>`}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.RoomNumber}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.TypeName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${room.Price}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.Capacity}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${room.Status === 'available' ? 'bg-green-100 text-green-800' : room.Status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                                ${room.Status.charAt(0).toUpperCase() + room.Status.slice(1)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="showRoomModal('edit', ${room.RoomID})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-red-600 hover:text-red-900" onclick="deleteRoom(${room.RoomID})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        Error loading rooms: ${error.message}
                    </td>
                </tr>
            `;
                });
        }

        function showRoomModal(mode, id = null) {
            const modal = document.getElementById('room-modal');
            const title = document.getElementById('room-modal-title');
            const form = document.getElementById('room-modal-form');

            modal.classList.remove('hidden');
            title.textContent = mode === 'add' ? 'Add Room' : 'Edit Room';
            form.reset();
            document.getElementById('room-modal-id').value = id || '';
            document.getElementById('room-images-preview').innerHTML = '';

            // Load room types first
            loadRoomTypes().then(() => {
                if (mode === 'edit' && id) {
                    // Load room data for editing
                    fetch(`admin_get_room.php?id=${id}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(room => {
                            if (room.error) {
                                throw new Error(room.error);
                            }

                            document.getElementById('room-modal-name').value = room.RoomNumber || '';
                            document.getElementById('room-modal-type').value = room.RoomTypeID || '';
                            document.getElementById('room-modal-price').value = room.Price || '';
                            document.getElementById('room-modal-guests').value = room.Capacity || '';
                            document.getElementById('room-modal-status').value = room.Status || 'available';

                            // Hiển thị ảnh nếu có
                            const imagesContainer = document.getElementById('room-images-preview');
                            if (room.images && room.images.length > 0) {
                                room.images.forEach(image => {
                                    const imgContainer = document.createElement('div');
                                    imgContainer.className = 'relative';
                                    imgContainer.innerHTML = `
                                <img src="${image.ImageURL}" class="w-full h-24 object-cover rounded">
                                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs" onclick="this.parentElement.remove()">
                                    ×
                                </button>
                            `;
                                    imagesContainer.appendChild(imgContainer);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error loading room:', error);
                            showErrorModal('Error', `Failed to load room data: ${error.message}`);
                            hideRoomModal();
                        });
                }
            }).catch(error => {
                console.error('Error loading room types:', error);
                showErrorModal('Error', `Failed to load room types: ${error.message}`);
            });
        }

        function hideRoomModal() {
            document.getElementById('room-modal').classList.add('hidden');
        }

        function deleteRoom(id) {
            if (confirm('Are you sure you want to delete this room?')) {
                const index = rooms.findIndex(r => r.id == id);
                if (index > -1) {
                    rooms.splice(index, 1);
                    renderAdminRooms();
                    showSuccessModal('Room Deleted', 'Room has been deleted.');
                }
            }
        }

        // Load admin bookings with filters
        function loadAdminBookings() {
            const dateRange = document.getElementById('booking-date-filter').value;
            const status = document.getElementById('booking-status-filter').value;
            const roomType = document.getElementById('booking-roomtype-filter').value;
            const search = document.getElementById('booking-search-input').value;

            const tbody = document.getElementById('admin-booking-table-body');
            tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading bookings...</td></tr>`;

            const params = new URLSearchParams({
                dateRange,
                status,
                roomType,
                search
            });

            fetch(`admin_get_bookings.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAdminBookings(data.bookings);
                    } else {
                        tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading bookings:', error);
                    tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error loading bookings</td></tr>`;
                });
        }

        // Render admin bookings table
        function renderAdminBookings(bookings) {
            const tbody = document.getElementById('admin-booking-table-body');
            
            if (!bookings || bookings.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No bookings found</td></tr>`;
                return;
            }

            tbody.innerHTML = '';
            bookings.forEach(booking => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${booking.BookingID}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${booking.GuestName}</div>
                        <div class="text-sm text-gray-500">${booking.GuestEmail}</div>
                        <div class="text-sm text-gray-500">${booking.GuestPhone || ''}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">Room ${booking.RoomNumber}</div>
                        <div class="text-sm text-gray-500">${booking.RoomType}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckinDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckoutDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <div>$${parseFloat(booking.TotalAmount).toFixed(2)}</div>
                        <div class="text-xs text-gray-500">${booking.Nights} nights</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${booking.StatusClass}">
                            ${booking.Status}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <button onclick="viewBookingDetails(${booking.BookingID})" class="text-blue-600 hover:text-blue-900 mr-2">View</button>
                        ${booking.Status === 'pending' ? 
                            `<button onclick="updateBookingStatus(${booking.BookingID}, 'confirmed')" class="text-green-600 hover:text-green-900 mr-2">Confirm</button>` : ''}
                        ${booking.Status !== 'cancelled' && booking.Status !== 'completed' ? 
                            `<button onclick="updateBookingStatus(${booking.BookingID}, 'cancelled')" class="text-red-600 hover:text-red-900">Cancel</button>` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Debounced search for better performance
        let searchTimeout;
        function debounceSearch() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadAdminBookings();
            }, 300);
        }

        function viewBookingDetails(bookingId) {
            // Find booking from current loaded data
            fetch(`admin_get_bookings.php?bookingId=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.bookings.length > 0) {
                        const booking = data.bookings[0];
                        const detailsHTML = `
                            <div class="text-left space-y-2">
                                <p><strong>Booking ID:</strong> #${booking.BookingID}</p>
                                <p><strong>Guest:</strong> ${booking.GuestName}</p>
                                <p><strong>Email:</strong> ${booking.GuestEmail}</p>
                                <p><strong>Phone:</strong> ${booking.GuestPhone || 'N/A'}</p>
                                <p><strong>Room:</strong> ${booking.RoomType} #${booking.RoomNumber}</p>
                                <p><strong>Check-in:</strong> ${booking.CheckinDate_formatted}</p>
                                <p><strong>Check-out:</strong> ${booking.CheckoutDate_formatted}</p>
                                <p><strong>Nights:</strong> ${booking.Nights}</p>
                                <p><strong>Total Amount:</strong> $${parseFloat(booking.TotalAmount).toFixed(2)}</p>
                                <p><strong>Status:</strong> <span class="px-2 py-1 rounded text-xs ${booking.StatusClass}">${booking.Status}</span></p>
                                <p><strong>Booked on:</strong> ${booking.CreatedAt_formatted}</p>
                            </div>
                        `;
                        showSuccessModal('Booking Details', detailsHTML);
                    } else {
                        showErrorModal('Error', 'Booking not found');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showErrorModal('Error', 'Failed to load booking details');
                });
        }

        function updateBookingStatus(bookingId, newStatus) {
            if (!confirm(`Are you sure you want to change booking #${bookingId} status to "${newStatus}"?`)) {
                return;
            }

            fetch('reception_update_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    status: newStatus,
                    adminUpdate: true
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Status Updated', `Booking #${bookingId} status has been updated to ${newStatus}.`);
                    loadAdminBookings(); // Reload to get fresh data
                } else {
                    showErrorModal('Error', data.message || 'Failed to update booking status');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('Error', 'Failed to update booking status');
            });
        }

        // Revenue Report Functions
        function loadRevenueReport() {
            const period = document.getElementById('revenue-period-filter').value;
            const roomType = document.getElementById('revenue-roomtype-filter').value;

            const params = new URLSearchParams({ period, roomType });

            // Show loading state
            showRevenueLoading();

            fetch(`admin_get_revenue.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderRevenueReport(data);
                    } else {
                        showRevenueError(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading revenue report:', error);
                    showRevenueError('Error loading revenue report');
                });
        }

        function renderRevenueReport(data) {
            // Update summary cards
            document.getElementById('total-revenue').textContent = `$${data.summary.totalRevenue.toLocaleString()}`;
            document.getElementById('total-bookings').textContent = data.summary.totalBookings.toLocaleString();
            document.getElementById('occupancy-rate').textContent = `${data.summary.occupancyRate}%`;
            document.getElementById('avg-booking-value').textContent = `$${data.summary.avgBookingValue.toFixed(2)}`;

            // Update growth indicators
            const revenueGrowth = data.summary.revenueGrowth;
            const revenueGrowthEl = document.getElementById('revenue-growth');
            const growthClass = revenueGrowth >= 0 ? 'text-green-600' : 'text-red-600';
            const growthIcon = revenueGrowth >= 0 ? '+' : '';
            revenueGrowthEl.className = `text-sm ${growthClass}`;
            revenueGrowthEl.textContent = `${growthIcon}${revenueGrowth}% from last period`;

            const bookingsGrowth = data.summary.bookingsGrowth;
            const bookingsGrowthEl = document.getElementById('bookings-growth');
            const bookingsGrowthClass = bookingsGrowth >= 0 ? 'text-green-600' : 'text-red-600';
            const bookingsGrowthIcon = bookingsGrowth >= 0 ? '+' : '';
            bookingsGrowthEl.className = `text-sm ${bookingsGrowthClass}`;
            bookingsGrowthEl.textContent = `${bookingsGrowthIcon}${bookingsGrowth}% from last period`;

            // Render top performing rooms
            renderTopRooms(data.topRooms);

            // Render room type revenue breakdown
            renderRoomTypeRevenue(data.roomTypeRevenue);

            // Render revenue chart
            renderRevenueChart(data.dailyTrend);
        }

        function renderTopRooms(topRooms) {
            const container = document.getElementById('top-rooms-list');
            
            if (!topRooms || topRooms.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No room data available</div>';
                return;
            }

            container.innerHTML = '';
            topRooms.slice(0, 5).forEach(room => {
                const occupancyClass = room.occupancyRate >= 80 ? 'bg-green-100 text-green-800' : 
                                      room.occupancyRate >= 60 ? 'bg-yellow-100 text-yellow-800' : 
                                      'bg-red-100 text-red-800';

                const roomDiv = document.createElement('div');
                roomDiv.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                roomDiv.innerHTML = `
                    <div>
                        <p class="font-medium">${room.TypeName} Room #${room.RoomNumber}</p>
                        <p class="text-sm text-gray-600">Revenue: $${parseFloat(room.revenue).toLocaleString()}</p>
                        <p class="text-sm text-gray-600">Bookings: ${room.bookings}</p>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs ${occupancyClass}">
                        ${room.occupancyRate}%
                    </span>
                `;
                container.appendChild(roomDiv);
            });
        }

        function renderRoomTypeRevenue(roomTypeRevenue) {
            const container = document.getElementById('room-type-revenue');
            
            if (!roomTypeRevenue || roomTypeRevenue.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500">No revenue data available</div>';
                return;
            }

            container.innerHTML = '';
            const colors = ['bg-blue-600', 'bg-green-600', 'bg-yellow-600', 'bg-purple-600', 'bg-red-600'];
            
            roomTypeRevenue.forEach((roomType, index) => {
                const colorClass = colors[index % colors.length];
                
                const typeDiv = document.createElement('div');
                typeDiv.className = 'flex items-center justify-between mb-4';
                typeDiv.innerHTML = `
                    <div class="flex-1">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">${roomType.TypeName}</span>
                            <span class="text-sm text-gray-600">$${parseFloat(roomType.revenue).toLocaleString()}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="${colorClass} h-2 rounded-full" style="width: ${roomType.percentage}%"></div>
                        </div>
                        <div class="flex justify-between mt-1">
                            <span class="text-xs text-gray-500">${roomType.bookings} bookings</span>
                            <span class="text-xs text-gray-500">${roomType.percentage}%</span>
                        </div>
                    </div>
                `;
                container.appendChild(typeDiv);
            });
        }

        function renderRevenueChart(dailyTrend) {
            const canvas = document.getElementById('revenue-chart');
            const ctx = canvas.getContext('2d');
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            if (!dailyTrend || dailyTrend.length === 0) {
                ctx.fillStyle = '#6B7280';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No trend data available', canvas.width / 2, canvas.height / 2);
                return;
            }

            // Simple line chart implementation
            const padding = 40;
            const chartWidth = canvas.width - 2 * padding;
            const chartHeight = canvas.height - 2 * padding;
            
            const maxRevenue = Math.max(...dailyTrend.map(d => parseFloat(d.revenue)));
            const minRevenue = Math.min(...dailyTrend.map(d => parseFloat(d.revenue)));
            const revenueRange = maxRevenue - minRevenue || 1;
            
            // Draw axes
            ctx.strokeStyle = '#E5E7EB';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, canvas.height - padding);
            ctx.lineTo(canvas.width - padding, canvas.height - padding);
            ctx.stroke();
            
            // Draw data line
            ctx.strokeStyle = '#3B82F6';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            dailyTrend.forEach((point, index) => {
                const x = padding + (index / (dailyTrend.length - 1)) * chartWidth;
                const y = canvas.height - padding - ((parseFloat(point.revenue) - minRevenue) / revenueRange) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
                
                // Draw points
                ctx.fillStyle = '#3B82F6';
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, 2 * Math.PI);
                ctx.fill();
            });
            
            ctx.stroke();
            
            // Draw labels
            ctx.fillStyle = '#6B7280';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            
            // Y-axis labels
            for (let i = 0; i <= 5; i++) {
                const value = minRevenue + (revenueRange * i / 5);
                const y = canvas.height - padding - (i / 5) * chartHeight;
                ctx.textAlign = 'right';
                ctx.fillText(`$${value.toFixed(0)}`, padding - 10, y + 4);
            }
            
            // X-axis labels (show only a few dates)
            const labelInterval = Math.ceil(dailyTrend.length / 6);
            dailyTrend.forEach((point, index) => {
                if (index % labelInterval === 0) {
                    const x = padding + (index / (dailyTrend.length - 1)) * chartWidth;
                    const date = new Date(point.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    ctx.textAlign = 'center';
                    ctx.fillText(date, x, canvas.height - padding + 20);
                }
            });
        }

        function showRevenueLoading() {
            document.getElementById('total-revenue').textContent = '$0';
            document.getElementById('total-bookings').textContent = '0';
            document.getElementById('occupancy-rate').textContent = '0%';
            document.getElementById('avg-booking-value').textContent = '$0';
            document.getElementById('revenue-growth').textContent = 'Loading...';
            document.getElementById('bookings-growth').textContent = 'Loading...';
            
            document.getElementById('top-rooms-list').innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Loading top performing rooms...</p>
                </div>
            `;
            
            document.getElementById('room-type-revenue').innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Loading revenue breakdown...</p>
                </div>
            `;
        }

        function showRevenueError(message) {
            document.getElementById('revenue-growth').textContent = 'Error loading data';
            document.getElementById('bookings-growth').textContent = 'Error loading data';
            
            document.getElementById('top-rooms-list').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>${message}</p>
                </div>
            `;
            
            document.getElementById('room-type-revenue').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>${message}</p>
                </div>
            `;
        }

        // Export and Print functions
        function exportRevenueReport() {
            const period = document.getElementById('revenue-period-filter').value;
            const roomType = document.getElementById('revenue-roomtype-filter').value;
            
            // Create CSV content
            let csvContent = "Revenue Report\\n";
            csvContent += `Period: ${period}\\n`;
            csvContent += `Room Type Filter: ${roomType}\\n\\n`;
            
            // Add summary data
            csvContent += "Summary\\n";
            csvContent += `Total Revenue,${document.getElementById('total-revenue').textContent}\\n`;
            csvContent += `Total Bookings,${document.getElementById('total-bookings').textContent}\\n`;
            csvContent += `Occupancy Rate,${document.getElementById('occupancy-rate').textContent}\\n`;
            csvContent += `Average Booking Value,${document.getElementById('avg-booking-value').textContent}\\n\\n`;
            
            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `revenue_report_${period}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            showSuccessModal('Export Complete', 'Revenue report has been exported successfully.');
        }

        function printRevenueReport() {
            window.print();
        }

        // Banner carousel functionality
        let currentBannerIndex = 0;
        // const bannerInterval = 5000; // 5 seconds (removed duplicate declaration)

        function showBanner(index) {
            const bannerImage = document.getElementById('banner-image');
            const bannerRoomName = document.getElementById('banner-room-name');
            const bannerRoomType = document.getElementById('banner-room-type');
            const bannerRoomDesc = document.getElementById('banner-room-desc');
            const bannerViewBtn = document.getElementById('banner-view-btn');

            const room = rooms[index];
            if (room) {
                bannerImage.src = room.image;
                bannerRoomName.textContent = room.name;
                bannerRoomType.textContent = room.type.charAt(0).toUpperCase() + room.type.slice(1);
                bannerRoomDesc.textContent = room.description;
                bannerViewBtn.onclick = () => viewRoomDetails(room.id);
            }
        }

        function nextBanner() {
            currentBannerIndex = (currentBannerIndex + 1) % rooms.length;
            showBanner(currentBannerIndex);
        }

        function prevBanner() {
            currentBannerIndex = (currentBannerIndex - 1 + rooms.length) % rooms.length;
            showBanner(currentBannerIndex);
        }

        // Banner Featured Rooms Carousel
        const bannerRooms = [rooms[1], rooms[2], rooms[5]]; // Deluxe, Executive Suite, Presidential Suite
        let bannerIndex = 0;
        let bannerInterval = null;

        function showBannerRoom(idx) {
            const room = bannerRooms[idx];
            document.getElementById('banner-image').src = room.image;
            document.getElementById('banner-room-name').textContent = room.name;
            document.getElementById('banner-room-type').textContent = room.type.charAt(0).toUpperCase() + room.type.slice(1);
            document.getElementById('banner-room-desc').textContent = room.description;
            document.getElementById('banner-view-btn').onclick = function () {
                viewRoomDetails(room.id);
            };
        }
        function nextBannerRoom() {
            bannerIndex = (bannerIndex + 1) % bannerRooms.length;
            showBannerRoom(bannerIndex);
        }
        function prevBannerRoom() {
            bannerIndex = (bannerIndex - 1 + bannerRooms.length) % bannerRooms.length;
            showBannerRoom(bannerIndex);
        }
        function startBannerAuto() {
            if (bannerInterval) clearInterval(bannerInterval);
            bannerInterval = setInterval(nextBannerRoom, 5000);
        }
        document.addEventListener('DOMContentLoaded', function () {
            // ...existing code...
            // Banner controls
            document.getElementById('banner-prev').onclick = function () {
                prevBannerRoom();
                startBannerAuto();
            };
            document.getElementById('banner-next').onclick = function () {
                nextBannerRoom();
                startBannerAuto();
            };
            showBannerRoom(bannerIndex);
            startBannerAuto();
        });

        // Reception Panel logic
        function renderReceptionCheckinList(filter = '') {
            const tbody = document.querySelector('#reception-checkin-tab table tbody');
            if (!tbody) return;

            tbody.innerHTML = `
        <tr>
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                <i class="fas fa-spinner fa-spin mr-2"></i> Loading bookings...
            </td>
        </tr>
    `;

            // Thêm filter status vào URL
            const statusFilter = document.getElementById('reception-status-filter')?.value || '';
            let url = 'reception_get_bookings.php';
            if (statusFilter) {
                url += `?status=${encodeURIComponent(statusFilter)}`;
            }

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const bookings = data.bookings;

                        tbody.innerHTML = bookings.length ? '' : `
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No bookings found
                        </td>
                    </tr>
                `;

                        bookings.forEach(booking => {
                            const checkInDate = new Date(booking.CheckinDate);
                            const checkOutDate = new Date(booking.CheckoutDate);

                            tbody.innerHTML += `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.BookingID}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.GuestName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.RoomType} #${booking.RoomNumber}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()} ${checkInDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()} ${checkOutDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${booking.Status === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                ${booking.Status.charAt(0).toUpperCase() + booking.Status.slice(1)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button class="text-green-600 hover:text-green-900 font-medium" onclick="receptionCheckin(${booking.BookingID})">
                                <i class="fas fa-check-circle mr-1"></i> Check-in
                            </button>
                        </td>
                    </tr>`;
                        });
                    }
                });
        }

        function receptionCheckin(bookingID) {
            if (!confirm('Are you sure you want to check-in this booking?')) return;

            // Show loading state
            const btn = event.target;
            const originalBtnText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            // Send request to update booking status
            fetch('reception_update_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bookingID: bookingID,
                    status: 'checked-in'
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal('Check-in Successful', `Booking ${bookingID} has been checked in.`);
                        renderReceptionCheckinList();
                        renderReceptionRoomStatus();
                    } else {
                        throw new Error(data.message || 'Check-in failed');
                    }
                })
                .catch(error => {
                    showErrorModal('Check-in Error', error.message || 'An error occurred during check-in');
                })
                .finally(() => {
                    btn.innerHTML = originalBtnText;
                    btn.disabled = false;
                });
        }

        function receptionCheckin(bookingId) {
            const booking = adminBookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = 'checked-in';
                // Cập nhật trạng thái phòng
                const room = rooms.find(r => booking.roomName.includes(r.name));
                if (room) room.status = 'occupied';
                renderReceptionCheckinList();
                renderReceptionCheckoutList();
                renderReceptionRoomStatus();
                renderAdminBookings && renderAdminBookings(); // đồng bộ admin
                showSuccessModal('Check-in Successful', `Booking ${bookingId} has been checked in.`);
            }
        }

        function renderReceptionCheckoutList() {
            const tbody = document.querySelector('#reception-checkout-tab table tbody');
            if (!tbody) return;
            // Lọc booking đang checked-in
            const checkoutBookings = adminBookings.filter(b => b.status === 'checked-in');
            tbody.innerHTML = checkoutBookings.length ? '' : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No guests to check-out</td></tr>`;
            checkoutBookings.forEach(booking => {
                const checkInDate = new Date(booking.checkIn);
                const checkOutDate = new Date(booking.checkOut);
                tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.guestName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.roomName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Checked-in</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 font-medium" onclick="receptionCheckout('${booking.id}')">
                            <i class="fas fa-sign-out-alt mr-1"></i> Check-out
                        </button>
                    </td>
                </tr>`;
            });
        }

        function receptionCheckout(bookingId) {
            const booking = adminBookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = 'completed';
                // Cập nhật trạng thái phòng
                const room = rooms.find(r => booking.roomName.includes(r.name));
                if (room) room.status = 'dirty';
                renderReceptionCheckoutList();
                renderReceptionRoomStatus();
                renderAdminBookings && renderAdminBookings(); // đồng bộ admin
                showSuccessModal('Check-out Successful', `Booking ${bookingId} has been checked out.`);
            }
        }

        function renderReceptionRoomStatus() {
            const tbody = document.querySelector('#reception-status-tab table tbody');
            if (!tbody) return;
            tbody.innerHTML = '';
            rooms.forEach(room => {
                let statusBadge = '';
                let cleanBtn = '';
                if (room.status === 'occupied') {
                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Occupied</span>';
                    cleanBtn = '<button class="text-gray-400 text-xs cursor-not-allowed" disabled><i class="fas fa-broom mr-1"></i> Do Not Disturb</button>';
                } else if (room.status === 'dirty') {
                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dirty</span>';
                    cleanBtn = `<button class="text-blue-600 hover:text-blue-900 text-xs" onclick="receptionCleanRoom(${room.id})"><i class="fas fa-broom mr-1"></i> Clean</button>`;
                } else if (room.status === 'maintenance') {
                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Maintenance</span>';
                    cleanBtn = '';
                } else {
                    statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>';
                    cleanBtn = `<button class="text-blue-600 hover:text-blue-900 text-xs" onclick="receptionCleanRoom(${room.id})"><i class="fas fa-broom mr-1"></i> Clean</button>`;
                }
                tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.type.charAt(0).toUpperCase() + room.type.slice(1)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${cleanBtn}</td>
                </tr>`;
            });
        }

        function receptionCleanRoom(roomId) {
            const room = rooms.find(r => r.id == roomId);
            if (room && room.status === 'dirty') {
                room.status = 'available';
                renderReceptionRoomStatus();
                showSuccessModal('Room Cleaned', `Room ${roomId} is now available.`);
            }
        }

        // Reception variables
        let checkinBookings = [];
        let checkoutBookings = [];
        let roomStatuses = [];
        let cleanupRequests = [];
        let checkinSearchTimeout;
        let checkoutSearchTimeout;

        // Load confirm bookings (pending only)
        let confirmSearchTimeout;
        function loadConfirmBookings() {
            fetch('reception_get_bookings.php?status=pending')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderConfirmBookings(data.bookings);
                    } else {
                        console.error('Error loading confirm bookings:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        // Render confirm bookings
        function renderConfirmBookings(bookings) {
            const tbody = document.getElementById('confirm-table-body');
            if (!tbody) return;

            if (!bookings || bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No pending bookings found</td></tr>';
                return;
            }

            tbody.innerHTML = '';
            bookings.forEach(booking => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${booking.BookingID}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${booking.GuestName}</div>
                        <div class="text-sm text-gray-500">${booking.GuestEmail}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">${booking.GuestPhone || 'N/A'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">Room ${booking.RoomNumber}</div>
                        <div class="text-sm text-gray-500">${booking.RoomType}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckinDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckoutDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${booking.TotalAmount}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex flex-wrap gap-1">
                            <button onclick="confirmBooking(${booking.BookingID})" 
                                class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs mr-1">
                                <i class="fas fa-check mr-1"></i> Confirm
                            </button>
                            <button onclick="cancelBooking(${booking.BookingID})" 
                                class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs">
                                <i class="fas fa-times mr-1"></i> Reject
                            </button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Search confirm bookings
        function searchConfirmBookings() {
            clearTimeout(confirmSearchTimeout);
            confirmSearchTimeout = setTimeout(() => {
                const bookingId = document.getElementById('confirm-booking-id').value;
                const guestName = document.getElementById('confirm-guest-name').value;
                
                let url = 'reception_get_bookings.php?status=pending';
                if (bookingId) url += `&bookingId=${bookingId}`;
                if (guestName) url += `&guestName=${guestName}`;
                
                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            renderConfirmBookings(data.bookings);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 300);
        }

        // Load check-in bookings (confirmed only)
        function loadCheckinBookings() {
            const tbody = document.getElementById('checkin-table-body');
            if (!tbody) return;

            tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading check-in bookings...</td></tr>';

            // Load only confirmed bookings for check-in
            fetch('reception_get_bookings.php?status=confirmed')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        checkinBookings = data.bookings;
                        renderCheckinBookings(checkinBookings);
                    } else {
                        tbody.innerHTML = `<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    console.error('Error loading check-in bookings:', error);
                    tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-red-500">Error loading bookings</td></tr>';
                });
        }

        // Render check-in bookings
        function renderCheckinBookings(bookings) {
            const tbody = document.getElementById('checkin-table-body');
            if (!tbody) return;

            if (!bookings || bookings.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No check-in bookings found</td></tr>';
                return;
            }

            tbody.innerHTML = '';
            bookings.forEach(booking => {
                const row = document.createElement('tr');
                row.className = 'hover:bg-gray-50';
                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#${booking.BookingID}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${booking.GuestName}</div>
                        <div class="text-sm text-gray-500">${booking.GuestEmail}</div>
                        <div class="text-sm text-gray-500">${booking.GuestPhone || ''}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">Room ${booking.RoomNumber}</div>
                        <div class="text-sm text-gray-500">${booking.RoomType}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckinDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.CheckoutDate_formatted}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${booking.StatusClass}">
                            ${booking.Status}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex flex-wrap gap-1">
                            ${booking.Status === 'confirmed' ? 
                                `<button onclick="processCheckin(${booking.BookingID})" class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs">
                                    <i class="fas fa-sign-in-alt mr-1"></i> Check-in Guest
                                </button>` : 
                                '<span class="text-gray-400 text-xs">Not ready for check-in</span>'
                            }
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Search check-in bookings
        function searchCheckinBookings() {
            clearTimeout(checkinSearchTimeout);
            checkinSearchTimeout = setTimeout(() => {
                const bookingId = document.getElementById('checkin-booking-id').value.toLowerCase();
                const guestName = document.getElementById('checkin-guest-name').value.toLowerCase();

                const filtered = checkinBookings.filter(booking => {
                    const matchesId = !bookingId || booking.BookingID.toString().includes(bookingId);
                    const matchesName = !guestName || booking.GuestName.toLowerCase().includes(guestName);
                    return matchesId && matchesName;
                });

                renderCheckinBookings(filtered);
            }, 300);
        }

        // Confirm booking
        function confirmBooking(bookingId) {
            if (!confirm('Confirm this booking? Guest will be able to check-in.')) return;

            fetch('reception_update_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    status: 'confirmed'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Booking Confirmed', 'Booking has been confirmed successfully.');
                    loadCheckinBookings(); // Refresh list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error confirming booking:', error);
                alert('Error confirming booking');
            });
        }

        // Process check-in
        function processCheckin(bookingId) {
            if (!confirm('Process check-in for this guest?')) return;

            fetch('reception_update_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    status: 'checked-in'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Check-in Complete', 'Guest has been checked in successfully.');
                    loadCheckinBookings(); // Refresh list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error processing check-in:', error);
                alert('Error processing check-in');
            });
        }

        // Cancel booking
        function cancelBooking(bookingId) {
            const reason = prompt('Reason for cancellation (optional):');
            if (reason === null) return; // User clicked cancel

            if (!confirm('Cancel this booking? This action cannot be undone.')) return;

            fetch('reception_update_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    status: 'cancelled',
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Booking Cancelled', 'Booking has been cancelled successfully.');
                    loadCheckinBookings(); // Refresh list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error cancelling booking:', error);
                alert('Error cancelling booking');
            });
        }

        // User cancel booking function
        function userCancelBooking(bookingId) {
            const reason = prompt('Please enter the reason for cancellation (optional):');
            if (reason === null) return; // User clicked cancel

            if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) return;

            fetch('user_cancel_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Booking Cancelled', 'Your booking has been cancelled successfully.');
                    displayBookingHistory(); // Refresh the booking list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error cancelling booking:', error);
                alert('Error cancelling booking. Please try again.');
            });
        }

        // User request checkout function
        function userRequestCheckout(bookingId) {
            if (!confirm('Request checkout for this booking? Reception will process your request.')) return;

            fetch('user_checkout_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    bookingID: bookingId,
                    checkoutTime: new Date().toISOString()
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessModal('Checkout Request Sent', 'Your checkout request has been sent to reception. They will process it shortly.');
                    displayBookingHistory(); // Refresh the booking list
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error requesting checkout:', error);
                alert('Error sending checkout request. Please try again.');
            });
        }

        // Print booking function (enhanced)
        function printBooking(bookingId) {
            // Simple print functionality - you could enhance this to show booking details
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                    <head><title>Booking ${bookingId}</title></head>
                    <body>
                        <h2>Booking Confirmation</h2>
                        <p>Booking ID: ${bookingId}</p>
                        <p>This is a simplified print view. You can enhance this with full booking details.</p>
                    </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // ...existing code...
            // Khi vào trang admin, mặc định hiển thị tab User Management
            if (window.location.href.includes('section=admin')) {
                showAdminTab('users');
            }
        });

        // Fix for room modal image input - check if element exists first
        const roomModalImage = document.getElementById('room-modal-image');
        if (roomModalImage) {
            roomModalImage.addEventListener('input', function () {
                const url = this.value;
                const preview = document.getElementById('room-image-preview');
                if (preview && url) {
                    preview.src = url;
                    preview.style.display = 'block';
                } else if (preview) {
                    preview.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>