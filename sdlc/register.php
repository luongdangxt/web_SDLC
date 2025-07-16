<?php
require_once 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$phone = $input['phone'] ?? '';
$password = $input['password'] ?? '';

// Validate input
if (empty($name) || empty($email) || empty($phone) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT * FROM user_ WHERE Email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// Default role is user (assuming role ID 3 is for regular users)
$roleId = 3;

// Insert new user
try {
    $stmt = $pdo->prepare("INSERT INTO user_ (Username, Password, Email, Fullname, Role, Phonenumber, CreatedAt) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $username = strtolower(str_replace(' ', '', $name)); // Generate username from name
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $result = $stmt->execute([$username, $hashedPassword, $email, $name, $roleId, $phone]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Registration successful']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>