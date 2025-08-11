<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user']['id'];
$name = $input['name'] ?? '';
$phone = $input['phone'] ?? '';
$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

// Validate required fields
if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Name and phone are required']);
    exit;
}

// Validate phone number format (basic validation)
if (!preg_match('/^[0-9+\-\s\(\)]{10,15}$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if user exists and get current data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // If changing password, validate current password
    if (!empty($currentPassword) || !empty($newPassword)) {
        if (empty($currentPassword) || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Both current and new password are required']);
            exit;
        }
        
        if (!password_verify($currentPassword, $user['Password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }
        
        // Update with new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET Fullname = ?, Phonenumber = ?, Password = ?, UpdatedAt = NOW() WHERE UserID = ?");
        $stmt->execute([$name, $phone, $hashedPassword, $userId]);
    } else {
        // Update without password change
        $stmt = $pdo->prepare("UPDATE users SET Fullname = ?, Phonenumber = ?, UpdatedAt = NOW() WHERE UserID = ?");
        $stmt->execute([$name, $phone, $userId]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Update session data
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['phone'] = $phone;
    
    // Return updated user data
    $userData = [
        'id' => $userId,
        'name' => $name,
        'email' => $_SESSION['user']['email'],
        'phone' => $phone,
        'role' => $_SESSION['user']['role'],
        'avatar' => $_SESSION['user']['avatar'] ?? null
    ];
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully',
        'user' => $userData
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 