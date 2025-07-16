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
    $stmt = $pdo->prepare("SELECT * FROM user_ WHERE Email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO user_ (Username, Password, Email, Fullname, Role, CreatedAt) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    $username = strtolower(str_replace(' ', '', $name));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt->execute([$username, $hashedPassword, $email, $name, $roleId]);
    
    echo json_encode(['success' => true, 'message' => 'User added successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>