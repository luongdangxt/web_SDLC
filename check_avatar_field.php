<?php
require_once 'cors_headers.php';
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
    // Get current avatar data
    $stmt = $pdo->prepare("SELECT Avatar, LENGTH(Avatar) as avatar_length FROM Users WHERE UserID = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check field information
    $stmt = $pdo->prepare("SHOW COLUMNS FROM Users LIKE 'Avatar'");
    $stmt->execute();
    $fieldInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user' => [
            'has_avatar' => !empty($user['Avatar']),
            'avatar_length' => $user['avatar_length'],
            'avatar_starts_with_data' => $user['Avatar'] ? strpos($user['Avatar'], 'data:image') === 0 : false,
            'avatar_mime_type' => $user['Avatar'] ? explode(';', explode(':', $user['Avatar'])[1])[0] : null
        ],
        'field_info' => [
            'type' => $fieldInfo['Type'],
            'null' => $fieldInfo['Null'],
            'key' => $fieldInfo['Key'],
            'default' => $fieldInfo['Default'],
            'extra' => $fieldInfo['Extra']
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 