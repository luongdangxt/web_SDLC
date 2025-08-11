<?php
require_once 'cors_headers.php';
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Name, email and password are required']);
    exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$password = $data['password'];
$phone = $data['phone'] ?? '';

// Validate input
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Generate username from name
    $username = strtolower(str_replace(' ', '', $name)); // Generate username from name
    
    // Check if username exists, if so, make it unique
    $originalUsername = $username;
    $counter = 1;
    
    do {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE Username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $username = $originalUsername . $counter;
            $counter++;
        } else {
            break;
        }
    } while ($counter < 100); // Prevent infinite loop
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO Users (Username, Password, Email, Fullname, Role, Phonenumber, CreatedAt) 
                          VALUES (?, ?, ?, ?, 3, ?, NOW())"); // Role 2 = User
    $stmt->execute([$username, $hashedPassword, $email, $name, $phone]);
    
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>