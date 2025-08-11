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
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user';

// Create unique username based on name
$baseUsername = strtolower(str_replace(' ', '', $name));
$username = $baseUsername;

// Validate input
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Get role ID based on role name
try {
    $stmt = $pdo->prepare("SELECT RoleID FROM role WHERE RoleName = ?");
    $stmt->execute([$role]);
    $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roleData) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    $roleId = $roleData['RoleID'];
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    // Check if username already exists, if so, make it unique
    $originalUsername = $username;
    $counter = 1;
    
    do {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $username = $originalUsername . $counter;
            $counter++;
        } else {
            break;
        }
    } while ($counter < 100); // Prevent infinite loop
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (Username, Password, Email, Fullname, Role, CreatedAt) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt->execute([$username, $hashedPassword, $email, $name, $roleId]);
    
    echo json_encode(['success' => true, 'message' => 'User added successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>