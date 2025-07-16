<?php
require_once 'db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

// Find user by email
$stmt = $pdo->prepare("SELECT u.*, r.RoleName, r.Permissions 
                       FROM user_ u 
                       JOIN role r ON u.Role = r.RoleID 
                       WHERE u.Email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['Password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit;
}

// Start session and store user data
session_start();
$_SESSION['user'] = [
    'id' => $user['UserID'],
    'name' => $user['Fullname'],
    'email' => $user['Email'],
    'phone' => $user['Phonenumber'],
    'role' => $user['RoleName'],
    'permissions' => json_decode($user['Permissions'], true)
];

// Update last login time
$updateStmt = $pdo->prepare("UPDATE user_ SET UpdatedAt = NOW() WHERE UserID = ?");
$updateStmt->execute([$user['UserID']]);

// Prepare user data to return
$userData = [
    'id' => $user['UserID'],
    'name' => $user['Fullname'],
    'email' => $user['Email'],
    'phone' => $user['Phonenumber'],
    'role' => $user['RoleName'],
    'permissions' => json_decode($user['Permissions'], true)
];

echo json_encode(['success' => true, 'user' => $userData]);
?>