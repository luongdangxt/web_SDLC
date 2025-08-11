<?php
require_once 'db.php';

header('Content-Type: application/json');

// Check if user is logged in
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT u.UserID, u.Fullname, u.Email, u.Phonenumber, u.Avatar, u.Username, 
                           r.RoleName, u.CreatedAt, u.UpdatedAt
                           FROM users u 
                           JOIN role r ON u.Role = r.RoleID 
                           WHERE u.UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Format dates for display
    $user['CreatedAt'] = date('F j, Y', strtotime($user['CreatedAt']));
    $user['UpdatedAt'] = $user['UpdatedAt'] ? date('F j, Y', strtotime($user['UpdatedAt'])) : 'Never';
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 