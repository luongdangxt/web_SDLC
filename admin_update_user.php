<?php
require_once 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' || !$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = $input['id'] ?? 0;
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user';

// Create unique username based on name + timestamp to avoid duplicates
$baseUsername = strtolower(str_replace(' ', '', $name));
$username = $baseUsername;

// Validate input
if (empty($id) || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    // Get role ID based on role name
    $stmt = $pdo->prepare("SELECT RoleID FROM role WHERE RoleName = ?");
    $stmt->execute([$role]);
    $roleData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$roleData) {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    $roleId = $roleData['RoleID'];
    
    // Check if email already exists for another user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE Email = ? AND UserID != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    // Check if username already exists for another user, if so, make it unique
    $originalUsername = $username;
    $counter = 1;
    
    do {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE Username = ? AND UserID != ?");
        $stmt->execute([$username, $id]);
        
        if ($stmt->rowCount() > 0) {
            $username = $originalUsername . $counter;
            $counter++;
        } else {
            break;
        }
    } while ($counter < 100); // Prevent infinite loop
    // Update user
    
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET Username = ?, Password = ?, Email = ?, 
                              Fullname = ?, Role = ?, UpdatedAt = NOW() 
                              WHERE UserID = ?");
        $stmt->execute([$username, $hashedPassword, $email, $name, $roleId, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET Username = ?, Email = ?, 
                              Fullname = ?, Role = ?, UpdatedAt = NOW() 
                              WHERE UserID = ?");
        $stmt->execute([$username, $email, $name, $roleId, $id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>