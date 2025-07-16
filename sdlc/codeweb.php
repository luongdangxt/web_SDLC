<?php
require_once 'db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user;
            header('Location: home.php');
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}







?>
<!DOCTYPE html>
<html lang="en">
<!-- Phần còn lại của file HTML -->



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
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

            <div class="hidden md:flex space-x-6">
                <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('search')">
                    <i class="fas fa-search mr-1"></i> Search Rooms
                </a>
                <div id="auth-links">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('login')">
                        <i class="fas fa-sign-in-alt mr-1"></i> Login
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('register')">
                        <i class="fas fa-user-plus mr-1"></i> Register
                    </a>
                </div>
                <div id="user-links" class="hidden">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('book')">
                        <i class="fas fa-calendar-check mr-1"></i> Book Room
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('history')">
                        <i class="fas fa-history mr-1"></i> Booking History
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('profile')">
                        <i class="fas fa-user mr-1"></i> Profile
                    </a>
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="logout()">
                        <i class="fas fa-sign-out-alt mr-1"></i> Logout
                    </a>
                </div>
                <div id="admin-links" class="hidden">
                    <a href="#" class="hover:text-blue-200 transition duration-200" onclick="showSection('admin')">
                        <i class="fas fa-tools mr-1"></i> Admin Panel
                    </a>
                </div>
                <div id="reception-links" class="hidden">
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
                    <a href="#" class="py-2 hover:text-blue-200 transition duration-200"
                        onclick="showSection('profile')">
                        <i class="fas fa-user mr-3"></i> Profile
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
                <h2 class="text-2xl font-bold mb-6 text-gray-800">Featured Rooms</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Room cards will be populated by JavaScript -->
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
                <form id="search-form" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="check-in-date" class="block text-sm font-medium text-gray-700 mb-1">Check-in
                            Date</label>
                        <input type="date" id="check-in-date"
                            class="date-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div>
                        <label for="check-out-date" class="block text-sm font-medium text-gray-700 mb-1">Check-out
                            Date</label>
                        <input type="date" id="check-out-date"
                            class="date-input w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                    </div>
                    <div>
                        <label for="guests" class="block text-sm font-medium text-gray-700 mb-1">Number of
                            Guests</label>
                        <select id="guests"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="1">1 Guest</option>
                            <option value="2" selected>2 Guests</option>
                            <option value="3">3 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="5">5 Guests</option>
                        </select>
                    </div>
                    <div>
                        <label for="room-type" class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                        <select id="room-type"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="all">All Types</option>
                            <option value="standard">Standard</option>
                            <option value="deluxe">Deluxe</option>
                            <option value="suite">Suite</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-semibold transition duration-200">
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
                                class="w-32 h-32 mx-auto rounded-full overflow-hidden mb-4 border-4 border-white shadow-md">
                                <img src="https://source.unsplash.com/random/300x300/?portrait" alt="Profile"
                                    class="w-full h-full object-cover">
                            </div>
                            <h3 class="text-xl font-semibold" id="profile-display-name">Guest User</h3>
                            <p class="text-gray-600" id="profile-display-email">guest@example.com</p>
                            <div class="mt-4">
                                <button id="profile-upload-btn"
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-camera mr-1"></i> Upload Photo
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
                                                Status</th>
                                            <th scope="col"
                                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="admin-user-table-body">
                                        <!-- User rows will be rendered here by JS -->
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
                                        <select
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
                                        <select
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Status</option>
                                            <option value="confirmed">Confirmed</option>
                                            <option value="pending">Pending</option>
                                            <option value="cancelled">Cancelled</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Room Type</label>
                                        <select
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Types</option>
                                            <option value="standard">Standard</option>
                                            <option value="deluxe">Deluxe</option>
                                            <option value="suite">Suite</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                        <div class="relative">
                                            <input type="text" placeholder="Search bookings..."
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
                                        <select
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
                                        <select
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            <option value="all">All Types</option>
                                            <option value="standard">Standard</option>
                                            <option value="deluxe">Deluxe</option>
                                            <option value="suite">Suite</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Export</label>
                                        <button
                                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-download mr-1"></i> Export Report
                                        </button>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Print</label>
                                        <button
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                            <i class="fas fa-print mr-1"></i> Print Report
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-blue-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                            <i class="fas fa-dollar-sign text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                                            <p class="text-2xl font-bold text-gray-900">$24,580</p>
                                            <p class="text-sm text-green-600">+12.5% from last month</p>
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
                                            <p class="text-2xl font-bold text-gray-900">156</p>
                                            <p class="text-sm text-green-600">+8.2% from last month</p>
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
                                            <p class="text-2xl font-bold text-gray-900">78.5%</p>
                                            <p class="text-sm text-green-600">+5.3% from last month</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-purple-500">
                                    <div class="flex items-center">
                                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                            <i class="fas fa-bed text-xl"></i>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Avg. Room Rate</p>
                                            <p class="text-2xl font-bold text-gray-900">$157.56</p>
                                            <p class="text-sm text-green-600">+3.1% from last month</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue Chart -->
                            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                                <h4 class="text-lg font-semibold mb-4">Revenue Trend</h4>
                                <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                                    <div class="text-center text-gray-500">
                                        <i class="fas fa-chart-line text-4xl mb-2"></i>
                                        <p>Revenue chart would be displayed here</p>
                                        <p class="text-sm">(Chart.js or similar library integration)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Top Performing Rooms -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <h4 class="text-lg font-semibold mb-4">Top Performing Rooms</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <p class="font-medium">Deluxe Room #301</p>
                                                <p class="text-sm text-gray-600">Revenue: $3,240</p>
                                            </div>
                                            <span
                                                class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">95%</span>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <p class="font-medium">Executive Suite #402</p>
                                                <p class="text-sm text-gray-600">Revenue: $2,980</p>
                                            </div>
                                            <span
                                                class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">88%</span>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div>
                                                <p class="font-medium">Standard Room #201</p>
                                                <p class="text-sm text-gray-600">Revenue: $2,450</p>
                                            </div>
                                            <span
                                                class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">82%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-white p-6 rounded-lg shadow-md">
                                    <h4 class="text-lg font-semibold mb-4">Revenue by Room Type</h4>
                                    <div class="space-y-4">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Deluxe Rooms</span>
                                            <div class="flex items-center">
                                                <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: 45%"></div>
                                                </div>
                                                <span class="text-sm text-gray-600">45%</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Suites</span>
                                            <div class="flex items-center">
                                                <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: 35%"></div>
                                                </div>
                                                <span class="text-sm text-gray-600">35%</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium">Standard Rooms</span>
                                            <div class="flex items-center">
                                                <div class="w-32 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: 20%">
                                                    </div>
                                                </div>
                                                <span class="text-sm text-gray-600">20%</span>
                                            </div>
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
                                    onclick="showReceptionTab('checkin')">
                                    <i class="fas fa-sign-in-alt mr-3"></i> Check-in
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
                        <div id="reception-checkin-tab">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-sign-in-alt mr-2 text-blue-600"></i> Guest Check-in
                            </h3>

                            <div class="mb-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="checkin-booking-id"
                                            class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                                        <div class="relative">
                                            <input type="text" id="checkin-booking-id"
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
                                            <input type="text" id="checkin-guest-name"
                                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                placeholder="Search by name">
                                            <div class="absolute left-3 top-2.5 text-gray-400">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        </div>
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
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                RES-2023-001</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">John Doe</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Deluxe Room
                                                #301</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Today, 14:00
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">June 15, 2023
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="text-green-600 hover:text-green-900 font-medium">
                                                    <i class="fas fa-check-circle mr-1"></i> Check-in
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                RES-2023-002</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Jane Smith
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Suite #402
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Today, 14:00
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">June 18, 2023
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="text-green-600 hover:text-green-900 font-medium">
                                                    <i class="fas fa-check-circle mr-1"></i> Check-in
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-white border rounded-lg p-6 shadow-sm">
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
                            </div>

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
                        </div>

                        <div id="reception-checkout-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-sign-out-alt mr-2 text-blue-600"></i> Guest Check-out
                            </h3>
                            <p class="text-gray-700">This section would display guests ready for check-out with options
                                to process payments and room inspection.</p>
                        </div>

                        <div id="reception-status-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-bed mr-2 text-blue-600"></i> Room Status Board
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                <div class="bg-white border rounded-lg p-4 shadow-sm">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium text-gray-700">Total Rooms</h4>
                                        <span
                                            class="bg-gray-100 text-gray-800 text-sm font-medium px-2.5 py-0.5 rounded">40</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="bg-green-50 p-2 rounded">
                                            <p class="text-xs text-green-800">Available</p>
                                            <p class="text-xl font-bold text-green-600">25</p>
                                        </div>
                                        <div class="bg-blue-50 p-2 rounded">
                                            <p class="text-xs text-blue-800">Occupied</p>
                                            <p class="text-xl font-bold text-blue-600">12</p>
                                        </div>
                                        <div class="bg-yellow-50 p-2 rounded">
                                            <p class="text-xs text-yellow-800">Maintenance</p>
                                            <p class="text-xl font-bold text-yellow-600">2</p>
                                        </div>
                                        <div class="bg-red-50 p-2 rounded">
                                            <p class="text-xs text-red-800">Dirty</p>
                                            <p class="text-xl font-bold text-red-600">1</p>
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
                                                Housekeeping</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                201</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Standard</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="text-blue-600 hover:text-blue-900 text-xs"
                                                    onclick="sendCleanupRequest('201')">
                                                    <i class="fas fa-broom mr-1"></i> Clean
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                301</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Deluxe</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Occupied</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">John Doe</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">June 15, 2023
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="text-gray-400 text-xs cursor-not-allowed" disabled>
                                                    <i class="fas fa-broom mr-1"></i> Do Not Disturb
                                                </button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                402</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Suite</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <span
                                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Dirty</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <button class="text-blue-600 hover:text-blue-900 text-xs"
                                                    onclick="sendCleanupRequest('402')">
                                                    <i class="fas fa-broom mr-1"></i> Clean
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="reception-requests-tab" class="hidden">
                            <h3 class="text-xl font-bold mb-4 flex items-center">
                                <i class="fas fa-bell mr-2 text-blue-600"></i> Guest Requests
                            </h3>
                            <p class="text-gray-700">This section would display guest service requests with options to
                                assign to staff and mark as completed.</p>
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
                    <input type="password" id="user-modal-password" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select id="user-modal-role" class="w-full px-3 py-2 border rounded-md">
                        <option value="admin">Admin</option>
                        <option value="reception">Reception</option>
                        <option value="user">Customer</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                    <select id="user-modal-status" class="w-full px-3 py-2 border rounded-md">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
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
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room Name</label>
                        <input type="text" id="room-modal-name" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Room Type</label>
                        <select id="room-modal-type" class="w-full px-3 py-2 border rounded-md" required>
                            <option value="standard">Standard</option>
                            <option value="deluxe">Deluxe</option>
                            <option value="suite">Suite</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Price per Night ($)</label>
                        <input type="number" id="room-modal-price" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Max Guests</label>
                        <input type="number" id="room-modal-guests" class="w-full px-3 py-2 border rounded-md" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Room Image URL</label>
                    <input type="url" id="room-modal-image" class="w-full px-3 py-2 border rounded-md" required>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Description</label>
                    <textarea id="room-modal-description" rows="3" class="w-full px-3 py-2 border rounded-md"
                        required></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Amenities</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center">
                            <input type="checkbox" value="WiFi" class="mr-2"> WiFi
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="TV" class="mr-2"> TV
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="AC" class="mr-2"> AC
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="Mini Bar" class="mr-2"> Mini Bar
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="Coffee Maker" class="mr-2"> Coffee Maker
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="Safe" class="mr-2"> Safe
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="Work Desk" class="mr-2"> Work Desk
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" value="Jacuzzi" class="mr-2"> Jacuzzi
                        </label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Status</label>
                    <select id="room-modal-status" class="w-full px-3 py-2 border rounded-md">
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="occupied">Occupied</option>
                    </select>
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
            // Initialize the page
            showSection('home');
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
            document.getElementById('forgot-form').addEventListener('submit', handleForgotPassword);

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
            const storedUser = localStorage.getItem('hotelBookingUser');
            if (storedUser) {
                currentUser = JSON.parse(storedUser);
                updateUIForUser();
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

                // Special case handling for the home section
                if (sectionId === 'home') {
                    displayFeaturedRooms();
                }

                // Special case for booking history
                if (sectionId === 'history' && currentUser) {
                    displayBookingHistory();
                }
            }

            // Scroll to top
            window.scrollTo(0, 0);
        }

        // Show admin tab
        function showAdminTab(tabId) {
            document.querySelectorAll('#admin-section > div > div > div[id$="-tab"]').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.getElementById(`admin-${tabId}-tab`).classList.remove('hidden');

            // Update active tab highlight in sidebar
            document.querySelectorAll('#admin-section > div > div > div > ul > li > a').forEach(link => {
                link.classList.remove('bg-gray-700');
                link.classList.add('hover:bg-gray-700');
            });
            event.currentTarget.classList.remove('hover:bg-gray-700');
            event.currentTarget.classList.add('bg-gray-700');
        }

        // Show reception tab
        function showReceptionTab(tabId) {
            document.querySelectorAll('#reception-section > div > div > div[id$="-tab"]').forEach(tab => {
                tab.classList.add('hidden');
            });
            document.getElementById(`reception-${tabId}-tab`).classList.remove('hidden');

            // Update active tab highlight in sidebar
            document.querySelectorAll('#reception-section > div > div > div > ul > li > a').forEach(link => {
                link.classList.remove('bg-blue-600');
                link.classList.add('hover:bg-blue-600');
            });
            event.currentTarget.classList.remove('hover:bg-blue-600');
            event.currentTarget.classList.add('bg-blue-600');
        }

        // Toggle mobile menu
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Display featured rooms on home page
        function displayFeaturedRooms() {
            const featuredRoomsContainer = document.querySelector('#home-section .grid');
            if (!featuredRoomsContainer) return;

            featuredRoomsContainer.innerHTML = '';

            // Show 3 featured rooms
            const featuredRooms = [rooms[1], rooms[2], rooms[5]];

            featuredRooms.forEach(room => {
                const roomCard = document.createElement('div');
                roomCard.className = 'bg-white rounded-xl overflow-hidden shadow-md room-card transition duration-200';
                roomCard.innerHTML = `
                    <img src="${room.image}" alt="${room.name}" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold">${room.name}</h3>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${room.type.charAt(0).toUpperCase() + room.type.slice(1)}</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2">${room.description}</p>
                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-lg font-bold text-blue-600">$${room.price}/night</span>
                            <button onclick="viewRoomDetails(${room.id})" class="text-blue-600 hover:text-blue-800 font-medium">
                                View Details <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                `;

                featuredRoomsContainer.appendChild(roomCard);
            });
        }

        // View room details for booking
        function viewRoomDetails(roomId, checkInDate = null, checkOutDate = null) {
            const room = rooms.find(r => r.id === roomId);
            if (!room) return;

            selectedRoom = room;

            if (checkInDate) {
                selectedDates.checkIn = checkInDate;
                document.getElementById('check-in-date').value = checkInDate;
            }

            if (checkOutDate) {
                selectedDates.checkOut = checkOutDate;
                document.getElementById('check-out-date').value = checkOutDate;
            }

            // Update room details container
            document.getElementById('book-room-name').textContent = room.name;
            document.getElementById('book-room-type').textContent = room.type.charAt(0).toUpperCase() + room.type.slice(1);
            document.getElementById('book-room-image').src = room.image;
            document.getElementById('book-room-guests').textContent = room.maxGuests;
            document.getElementById('book-room-price').textContent = `$${room.price}`;
            document.getElementById('book-room-description').textContent = room.description;

            // Update amenities
            const amenitiesContainer = document.getElementById('book-room-amenities');
            amenitiesContainer.innerHTML = '';
            room.amenities.forEach(amenity => {
                const amenityElement = document.createElement('span');
                amenityElement.className = 'bg-gray-100 text-gray-800 text-xs px-3 py-1 rounded-full flex items-center';
                amenityElement.innerHTML = `<i class="fas fa-check-circle text-blue-500 mr-1"></i> ${amenity}`;
                amenitiesContainer.appendChild(amenityElement);
            });

            // Update booking summary
            updateBookingSummary();

            // Show the booking form
            document.getElementById('no-room-selected').classList.add('hidden');
            document.getElementById('room-details-container').classList.remove('hidden');
            document.getElementById('booking-form-container').classList.remove('hidden');

            // Set email if user is logged in
            if (currentUser) {
                document.getElementById('booking-email').value = currentUser.email;
                document.getElementById('booking-name').value = currentUser.name;
                document.getElementById('booking-phone').value = currentUser.phone;
            }

            // Navigate to book section
            showSection('book');
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

        // Display search results
        function displaySearchResults(rooms, checkInDate, checkOutDate) {
            const resultsContainer = document.querySelector('#search-results .grid');
            const searchResultsSection = document.getElementById('search-results');

            if (!rooms.length) {
                resultsContainer.innerHTML = '<div class="col-span-1 md:col-span-3 text-center py-10">No rooms available matching your criteria. Please try different dates or room types.</div>';
                searchResultsSection.classList.remove('hidden');
                return;
            }

            resultsContainer.innerHTML = '';

            rooms.forEach(room => {
                const roomCard = document.createElement('div');
                roomCard.className = 'bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg room-card transition duration-200';
                roomCard.innerHTML = `
                    <img src="${room.image}" alt="${room.name}" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg font-bold">${room.name}</h3>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm font-medium">${room.type.charAt(0).toUpperCase() + room.type.slice(1)}</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-2 line-clamp-2">${room.description}</p>
                        <div class="mt-4">
                            <div class="flex flex-wrap gap-1 mb-3">
                                ${room.amenities.map(amenity =>
                    `<span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded-full">${amenity}</span>`
                ).join('')}
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-blue-600">$${room.price}/night</span>
                                <button onclick="viewRoomDetails(${room.id}, '${checkInDate}', '${checkOutDate}')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium transition duration-200">
                                    Book Now <i class="fas fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                resultsContainer.appendChild(roomCard);
            });

            searchResultsSection.classList.remove('hidden');
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

            // Giả lập thanh toán online
            if (paymentMethod === 'online') {
                showSuccessModal('Payment Gateway', 'Redirecting to payment gateway... (demo only)');
                setTimeout(function () {
                    finishBooking(name, phone, specialRequests, checkIn, checkOut, nights, totalPrice, 'paid');
                }, 1500);
                return;
            } else {
                finishBooking(name, phone, specialRequests, checkIn, checkOut, nights, totalPrice, 'unpaid');
            }
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

            // Filter bookings for current user
            // In a real app, this would be an API call with user ID
            // For demo, we'll show all bookings if the user is the demo user
            const userBookings = currentUser.email === 'user@example.com' ? bookings : [];

            const tbody = document.getElementById('booking-history-body');
            tbody.innerHTML = '';

            if (userBookings.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings found</td>
                    </tr>
                `;
                return;
            }

            userBookings.forEach(booking => {
                const checkInDate = new Date(booking.checkIn);
                const checkOutDate = new Date(booking.checkOut);

                const row = document.createElement('tr');

                let statusBadge = '';
                switch (booking.status.toLowerCase()) {
                    case 'confirmed':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>';
                        break;
                    case 'cancelled':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>';
                        break;
                    case 'pending':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                        break;
                    default:
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">N/A</span>';
                }

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.roomName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${booking.totalPrice}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        ${booking.status.toLowerCase() === 'confirmed' ? `
                            <button onclick="cancelBooking('${booking.id}')" class="text-red-600 hover:text-red-900 mr-3">
                                Cancel
                            </button>
                        ` : ''}
                        <button onclick="printBooking('${booking.id}')" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-print"></i>
                        </button>
                    </td>
                `;

                tbody.appendChild(row);
            });
        }

        // Cancel a booking
        function cancelBooking(bookingId) {
            if (!confirm('Are you sure you want to cancel this booking?')) return;

            // In a real app, this would be an API call
            // For demo, we'll update locally
            const booking = bookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = 'cancelled';
                showSuccessModal('Booking Cancelled', `Your booking ${bookingId} has been cancelled successfully.`);
                displayBookingHistory();
            }
        }

        // Print booking (simulated)
        function printBooking(bookingId) {
            // In a real app, this would open a print dialog with booking details
            alert(`Print functionality would show details for booking ${bookingId}`);
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

                // In a real app, currentPassword would be verified with the server
                if (currentUser.email === 'user@example.com' && currentPassword !== 'user123') {
                    showErrorModal('Profile Error', 'Current password is incorrect.');
                    return;
                }
            }

            // Update user
            currentUser.name = name;
            currentUser.phone = phone;

            // In a real app, this would be an API call
            localStorage.setItem('hotelBookingUser', JSON.stringify(currentUser));

            showSuccessModal('Profile Updated', 'Your profile has been updated successfully.');

            // Reset password fields
            document.getElementById('profile-current-password').value = '';
            document.getElementById('profile-new-password').value = '';
            document.getElementById('profile-confirm-password').value = '';

            // Update displayed profile info
            document.getElementById('profile-display-name').textContent = name;
            document.getElementById('profile-display-email').textContent = currentUser.email;
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
                    document.getElementById('profile-name').value = currentUser.name;
                    document.getElementById('profile-email').value = currentUser.email;
                    document.getElementById('profile-phone').value = currentUser.phone;
                    document.getElementById('profile-display-name').textContent = currentUser.name;
                    document.getElementById('profile-display-email').textContent = currentUser.email;
                }
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
        function renderAdminUsers(filter = '') {
            const tbody = document.getElementById('admin-user-table-body');
            let users = adminUsers;
            if (filter) {
                users = users.filter(u => u.name.toLowerCase().includes(filter.toLowerCase()) || u.email.toLowerCase().includes(filter.toLowerCase()));
            }
            tbody.innerHTML = users.length ? '' : `<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found</td></tr>`;
            users.forEach(user => {
                tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.role === 'admin' ? 'bg-purple-100 text-purple-800' : user.role === 'reception' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'}">${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="showUserModal('edit',${user.id})"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600 hover:text-red-900" onclick="deleteUser(${user.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // ...existing code...
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
        });

        function showUserModal(mode, id = null) {
            document.getElementById('user-modal').classList.remove('hidden');
            if (mode === 'add') {
                document.getElementById('user-modal-title').textContent = 'Add User';
                document.getElementById('user-modal-id').value = '';
                document.getElementById('user-modal-name').value = '';
                document.getElementById('user-modal-email').value = '';
                document.getElementById('user-modal-role').value = 'user';
                document.getElementById('user-modal-status').value = 'active';
            } else {
                document.getElementById('user-modal-title').textContent = 'Edit User';
                const user = adminUsers.find(u => u.id == id);
                if (user) {
                    document.getElementById('user-modal-id').value = user.id;
                    document.getElementById('user-modal-name').value = user.name;
                    document.getElementById('user-modal-email').value = user.email;
                    document.getElementById('user-modal-role').value = user.role;
                    document.getElementById('user-modal-status').value = user.status;
                }
            }
        }
        function hideUserModal() {
            document.getElementById('user-modal').classList.add('hidden');
        }
        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                adminUsers = adminUsers.filter(u => u.id != id);
                renderAdminUsers();
                showSuccessModal('User Deleted', 'User has been deleted.');
            }
        }

        // Room management functions
        function renderAdminRooms(filter = '') {
            const tbody = document.getElementById('admin-room-table-body');
            let filteredRooms = rooms;
            if (filter) {
                filteredRooms = rooms.filter(r => r.name.toLowerCase().includes(filter.toLowerCase()) || r.type.toLowerCase().includes(filter.toLowerCase()));
            }
            tbody.innerHTML = filteredRooms.length ? '' : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No rooms found</td></tr>`;
            filteredRooms.forEach(room => {
                tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${room.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.name}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${room.type === 'suite' ? 'bg-purple-100 text-purple-800' : room.type === 'deluxe' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}">${room.type.charAt(0).toUpperCase() + room.type.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${room.price}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${room.maxGuests}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="showRoomModal('edit',${room.id})"><i class="fas fa-edit"></i></button>
                        <button class="text-red-600 hover:text-red-900" onclick="deleteRoom(${room.id})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>`;
            });
        }

        function showRoomModal(mode, id = null) {
            document.getElementById('room-modal').classList.remove('hidden');
            if (mode === 'add') {
                document.getElementById('room-modal-title').textContent = 'Add Room';
                document.getElementById('room-modal-id').value = '';
                document.getElementById('room-modal-form').reset();
            } else {
                document.getElementById('room-modal-title').textContent = 'Edit Room';
                const room = rooms.find(r => r.id == id);
                if (room) {
                    document.getElementById('room-modal-id').value = room.id;
                    document.getElementById('room-modal-name').value = room.name;
                    document.getElementById('room-modal-type').value = room.type;
                    document.getElementById('room-modal-price').value = room.price;
                    document.getElementById('room-modal-guests').value = room.maxGuests;
                    document.getElementById('room-modal-image').value = room.image;
                    document.getElementById('room-modal-description').value = room.description;

                    // Reset checkboxes
                    document.querySelectorAll('#room-modal-form input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = room.amenities.includes(checkbox.value);
                    });
                }
            }
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

        // Booking management functions
        function renderAdminBookings() {
            const tbody = document.getElementById('admin-booking-table-body');
            tbody.innerHTML = adminBookings.length ? '' : `<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No bookings found</td></tr>`;
            adminBookings.forEach(booking => {
                const checkInDate = new Date(booking.checkIn);
                const checkOutDate = new Date(booking.checkOut);

                let statusBadge = '';
                switch (booking.status.toLowerCase()) {
                    case 'confirmed':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Confirmed</span>';
                        break;
                    case 'cancelled':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Cancelled</span>';
                        break;
                    case 'pending':
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                        break;
                    default:
                        statusBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">N/A</span>';
                }

                tbody.innerHTML += `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${booking.id}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.guestName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${booking.roomName}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkInDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${checkOutDate.toLocaleDateString()}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$${booking.totalPrice}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${statusBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-blue-600 hover:text-blue-900 mr-3" onclick="viewBookingDetails('${booking.id}')"><i class="fas fa-eye"></i></button>
                        <button class="text-green-600 hover:text-green-900 mr-3" onclick="updateBookingStatus('${booking.id}', 'confirmed')"><i class="fas fa-check"></i></button>
                        <button class="text-red-600 hover:text-red-900" onclick="updateBookingStatus('${booking.id}', 'cancelled')"><i class="fas fa-times"></i></button>
                    </td>
                </tr>`;
            });
        }

        function viewBookingDetails(bookingId) {
            const booking = adminBookings.find(b => b.id === bookingId);
            if (booking) {
                showSuccessModal('Booking Details', `Booking ID: ${booking.id}\nGuest: ${booking.guestName}\nRoom: ${booking.roomName}\nTotal: $${booking.totalPrice}\nStatus: ${booking.status}`);
            }
        }

        function updateBookingStatus(bookingId, newStatus) {
            const booking = adminBookings.find(b => b.id === bookingId);
            if (booking) {
                booking.status = newStatus;
                renderAdminBookings();
                showSuccessModal('Status Updated', `Booking ${bookingId} status has been updated to ${newStatus}.`);
            }
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
        function renderReceptionCheckinList() {
            const tbody = document.querySelector('#reception-checkin-tab table tbody');
            if (!tbody) return;
            // Lọc booking có trạng thái 'pending' hoặc 'confirmed' và chưa checked-in
            const checkinBookings = adminBookings.filter(b => b.status === 'pending' || b.status === 'confirmed');
            tbody.innerHTML = checkinBookings.length ? '' : `<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings to check-in</td></tr>`;
            checkinBookings.forEach(booking => {
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
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button class="text-green-600 hover:text-green-900 font-medium" onclick="receptionCheckin('${booking.id}')">
                            <i class="fas fa-check-circle mr-1"></i> Check-in
                        </button>
                    </td>
                </tr>`;
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

        // Khi chuyển tab lễ tân, gọi render tương ứng
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('reception-checkin-tab')) renderReceptionCheckinList();
            if (document.getElementById('reception-checkout-tab')) renderReceptionCheckoutList();
            if (document.getElementById('reception-status-tab')) renderReceptionRoomStatus();
        });
    </script>
</body>

</html>