<?php
require_once 'cors_headers.php';
require_once 'db.php';
session_start();

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$name = trim($data['name'] ?? '');
$phone = trim($data['phone'] ?? '');
$currentPassword = $data['currentPassword'] ?? '';
$newPassword = $data['newPassword'] ?? '';

// Validate input
if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // Verify current password if changing password
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required to change password']);
            exit;
        }
        
        // Get current password hash
        $stmt = $pdo->prepare("SELECT Password FROM Users WHERE UserID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['Password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }
        
        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE Users SET Fullname = ?, Phonenumber = ?, Password = ?, UpdatedAt = NOW() WHERE UserID = ?");
        $stmt->execute([$name, $phone, $hashedPassword, $userId]);
    } else {
        // Update without password change
        $stmt = $pdo->prepare("UPDATE Users SET Fullname = ?, Phonenumber = ?, UpdatedAt = NOW() WHERE UserID = ?");
        $stmt->execute([$name, $phone, $userId]);
    }
    
    // Update session data
    $_SESSION['user']['fullname'] = $name;
    $_SESSION['user']['phone'] = $phone;
    
    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 