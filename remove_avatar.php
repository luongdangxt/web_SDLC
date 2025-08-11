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
    // Update database to remove avatar
    $stmt = $pdo->prepare("UPDATE users SET Avatar = NULL, UpdatedAt = NOW() WHERE UserID = ?");
    $stmt->execute([$userId]);
    
    // Update session data
    $_SESSION['user']['avatar'] = null;
    
    echo json_encode([
        'success' => true,
        'message' => 'Avatar removed successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 