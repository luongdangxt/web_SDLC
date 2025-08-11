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

try {
    $userId = $_SESSION['user']['id'];
    
    // Lấy thông tin user
    $stmt = $pdo->prepare("
        SELECT u.UserID, u.Username, u.Email, u.Fullname, u.Phonenumber, u.Avatar, u.CreatedAt,
               r.RoleName
        FROM Users u
        JOIN Role r ON u.Role = r.RoleID
        WHERE u.UserID = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 